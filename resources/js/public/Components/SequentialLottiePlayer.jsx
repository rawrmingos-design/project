import React, { useEffect, useMemo, useRef, useState } from 'react';

function clearTimers(timersRef) {
    timersRef.current.forEach((timerId) => window.clearTimeout(timerId));
    timersRef.current = [];
}

export default function SequentialLottiePlayer({
    sequence = [],
    switchAfterMs = 2150,
    loopLast = false,
    className = '',
    ariaHidden = true,
}) {
    const playerRef = useRef(null);
    const timersRef = useRef([]);
    const normalizedSequence = useMemo(
        () => (Array.isArray(sequence) ? sequence.map((item) => String(item || '').trim()).filter(Boolean) : []),
        [sequence],
    );
    const [isReady, setIsReady] = useState(() => Boolean(typeof window !== 'undefined' && window.customElements?.get('lottie-player')));

    useEffect(() => {
        if (normalizedSequence.length === 0 || typeof window === 'undefined') {
            setIsReady(false);
            return undefined;
        }

        if (window.customElements?.get('lottie-player')) {
            setIsReady(true);
            return undefined;
        }

        const existingScript = document.querySelector('script[data-lottie-player-loader="public-sequence"]');
        if (existingScript) {
            const onLoad = () => setIsReady(Boolean(window.customElements?.get('lottie-player')));
            existingScript.addEventListener('load', onLoad);
            existingScript.addEventListener('error', onLoad);

            return () => {
                existingScript.removeEventListener('load', onLoad);
                existingScript.removeEventListener('error', onLoad);
            };
        }

        const script = document.createElement('script');
        script.src = '/assets/vendor/lottie/lottie-player.js';
        script.async = true;
        script.defer = true;
        script.dataset.lottiePlayerLoader = 'public-sequence';

        const onLoad = () => setIsReady(Boolean(window.customElements?.get('lottie-player')));
        script.addEventListener('load', onLoad);
        script.addEventListener('error', onLoad);
        document.head.appendChild(script);

        return () => {
            script.removeEventListener('load', onLoad);
            script.removeEventListener('error', onLoad);
        };
    }, [normalizedSequence.length]);

    useEffect(() => {
        if (!isReady || normalizedSequence.length === 0 || typeof window === 'undefined') {
            return undefined;
        }

        const player = playerRef.current;
        if (!player) {
            return undefined;
        }

        clearTimers(timersRef);

        const playSource = (src, shouldLoop = false) => {
            if (!src) {
                return;
            }

            if (typeof player.load === 'function') {
                player.load(src);
            } else {
                player.setAttribute('src', src);
            }

            player.setAttribute('loop', shouldLoop ? 'true' : 'false');

            if (typeof player.stop === 'function') {
                player.stop();
            }

            timersRef.current.push(window.setTimeout(() => {
                if (typeof player.play === 'function') {
                    player.play();
                }
            }, 40));
        };

        if (normalizedSequence.length === 1) {
            playSource(normalizedSequence[0], loopLast);
        } else {
            playSource(normalizedSequence[0], false);

            timersRef.current.push(window.setTimeout(() => {
                playSource(normalizedSequence[1], loopLast);
            }, Math.max(180, Number(switchAfterMs) || 2150)));
        }

        return () => {
            clearTimers(timersRef);
        };
    }, [isReady, loopLast, normalizedSequence, switchAfterMs]);

    useEffect(() => () => {
        if (typeof window !== 'undefined') {
            clearTimers(timersRef);
        }
    }, []);

    if (!isReady || normalizedSequence.length === 0) {
        return null;
    }

    return (
        <lottie-player
            ref={playerRef}
            background="transparent"
            speed="1"
            className={className}
            aria-hidden={ariaHidden ? 'true' : undefined}
        />
    );
}
