const fs = require('node:fs');
const path = require('node:path');

const DEFAULT_TARGET = process.env.PSI_URL || process.argv[2] || 'https://istanatopup.imhaf.online/id';
const API_KEY = process.env.PSI_API_KEY || '';
const STRATEGIES = ['mobile', 'desktop'];
const CATEGORIES = ['performance', 'accessibility', 'best-practices', 'seo'];
const SEO_MIN = Number(process.env.PSI_SEO_MIN || '80');
const PERF_MIN = Number(process.env.PSI_PERF_MIN || '50');

function toScore(value) {
    if (typeof value !== 'number' || Number.isNaN(value)) {
        return null;
    }

    return Math.round(value * 100);
}

function getQueryUrl(targetUrl, strategy) {
    const endpoint = new URL('https://www.googleapis.com/pagespeedonline/v5/runPagespeed');
    endpoint.searchParams.set('url', targetUrl);
    endpoint.searchParams.set('strategy', strategy);
    endpoint.searchParams.set('locale', 'id');
    endpoint.searchParams.append('category', 'performance');
    endpoint.searchParams.append('category', 'accessibility');
    endpoint.searchParams.append('category', 'best-practices');
    endpoint.searchParams.append('category', 'seo');

    if (API_KEY) {
        endpoint.searchParams.set('key', API_KEY);
    }

    return endpoint.toString();
}

function topOpportunities(audits) {
    if (!audits || typeof audits !== 'object') {
        return [];
    }

    return Object.values(audits)
        .filter((item) => item && typeof item === 'object')
        .filter((item) => item.details?.type === 'opportunity')
        .filter((item) => typeof item.numericValue === 'number' && item.numericValue > 0)
        .filter((item) => typeof item.score === 'number' && item.score < 1)
        .sort((a, b) => (b.numericValue || 0) - (a.numericValue || 0))
        .slice(0, 8)
        .map((item) => ({
            id: item.id || '',
            title: item.title || item.id || 'unknown',
            score: toScore(item.score),
            potentialSavingsMs: Math.round(item.numericValue || 0),
            displayValue: item.displayValue || '',
        }));
}

async function fetchPsi(targetUrl, strategy) {
    const queryUrl = getQueryUrl(targetUrl, strategy);
    const response = await fetch(queryUrl, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
        },
    });

    if (!response.ok) {
        const body = await response.text();
        if (response.status === 429) {
            throw new Error(
                `PSI ${strategy} failed (429 quota exceeded). ` +
                `Set PSI_API_KEY to your own Google API key, lalu coba lagi.\n` +
                `Body: ${body.slice(0, 700)}`
            );
        }
        throw new Error(`PSI ${strategy} failed (${response.status}): ${body.slice(0, 700)}`);
    }

    return response.json();
}

async function preflightTarget(targetUrl) {
    try {
        const response = await fetch(targetUrl, {
            method: 'GET',
            redirect: 'follow',
            signal: AbortSignal.timeout(15_000),
        });

        if (!response.ok) {
            console.warn(`[preflight] target responded with status ${response.status}: ${targetUrl}`);
            return;
        }

        console.log(`[preflight] target reachable: ${targetUrl} (${response.status})`);
    } catch (error) {
        console.warn(
            `[preflight] target not reachable from local runtime: ${targetUrl}\n` +
            `           Pastikan Laragon/app aktif dan tunnel/domain publik benar jika ingin audit domain publik.`
        );
    }
}

function summarize(result, strategy) {
    const categories = result?.lighthouseResult?.categories || {};
    const audits = result?.lighthouseResult?.audits || {};
    const finalUrl = result?.lighthouseResult?.finalDisplayedUrl || result?.id || DEFAULT_TARGET;
    const fetchTime = result?.lighthouseResult?.fetchTime || null;

    const categoryScores = {};
    for (const categoryKey of CATEGORIES) {
        categoryScores[categoryKey] = toScore(categories?.[categoryKey]?.score);
    }

    const fcp = audits['first-contentful-paint']?.displayValue || null;
    const lcp = audits['largest-contentful-paint']?.displayValue || null;
    const cls = audits['cumulative-layout-shift']?.displayValue || null;
    const tbt = audits['total-blocking-time']?.displayValue || null;
    const speedIndex = audits['speed-index']?.displayValue || null;

    return {
        strategy,
        finalUrl,
        fetchTime,
        scores: categoryScores,
        metrics: { fcp, lcp, cls, tbt, speedIndex },
        opportunities: topOpportunities(audits),
    };
}

function printSummary(summary) {
    const { strategy, finalUrl, scores, metrics, opportunities } = summary;
    console.log('');
    console.log(`=== ${strategy.toUpperCase()} ===`);
    console.log(`URL: ${finalUrl}`);
    console.log(`Scores => SEO: ${scores.seo ?? '-'} | Performance: ${scores.performance ?? '-'} | Accessibility: ${scores.accessibility ?? '-'} | Best Practices: ${scores['best-practices'] ?? '-'}`);
    console.log(`Core Metrics => FCP: ${metrics.fcp ?? '-'} | LCP: ${metrics.lcp ?? '-'} | CLS: ${metrics.cls ?? '-'} | TBT: ${metrics.tbt ?? '-'} | Speed Index: ${metrics.speedIndex ?? '-'}`);

    if (opportunities.length > 0) {
        console.log('Top opportunities:');
        opportunities.forEach((op, index) => {
            console.log(`  ${index + 1}. ${op.title} | score=${op.score ?? '-'} | savings≈${op.potentialSavingsMs}ms ${op.displayValue ? `| ${op.displayValue}` : ''}`);
        });
    } else {
        console.log('Top opportunities: none');
    }
}

function ensureOutputDir() {
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    const outputDir = path.join(process.cwd(), 'test-results', 'pagespeed', timestamp);
    fs.mkdirSync(outputDir, { recursive: true });
    return outputDir;
}

function writeJson(filePath, data) {
    fs.writeFileSync(filePath, JSON.stringify(data, null, 2));
}

function shouldFail(summaryList) {
    return summaryList.some((item) => {
        const seo = item.scores.seo ?? 0;
        const perf = item.scores.performance ?? 0;
        return seo < SEO_MIN || perf < PERF_MIN;
    });
}

async function main() {
    const targetUrl = DEFAULT_TARGET;
    console.log(`Running PSI audit for: ${targetUrl}`);
    console.log(`Thresholds => SEO >= ${SEO_MIN}, Performance >= ${PERF_MIN}`);
    await preflightTarget(targetUrl);

    const outputDir = ensureOutputDir();
    const summaries = [];
    const rawResults = {};

    for (const strategy of STRATEGIES) {
        const raw = await fetchPsi(targetUrl, strategy);
        rawResults[strategy] = raw;
        const summary = summarize(raw, strategy);
        summaries.push(summary);
        printSummary(summary);

        writeJson(path.join(outputDir, `pagespeed-${strategy}.json`), raw);
        writeJson(path.join(outputDir, `summary-${strategy}.json`), summary);
    }

    const compactSummary = {
        auditedAt: new Date().toISOString(),
        targetUrl,
        thresholds: { seo: SEO_MIN, performance: PERF_MIN },
        summaries,
    };
    writeJson(path.join(outputDir, 'summary-all.json'), compactSummary);

    console.log('');
    console.log(`Reports saved to: ${outputDir}`);

    if (shouldFail(summaries)) {
        console.error('Threshold check failed: one or more strategy scores are below minimum target.');
        process.exit(2);
    }
}

main().catch((error) => {
    console.error('PageSpeed audit failed.');
    console.error(error?.message || error);
    process.exit(1);
});
