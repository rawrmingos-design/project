@extends('template.template')


@section('content')
@include('../navbar')
<div class="container pt-4 md:mx-auto md:pt-16">
    <div class="rounded-md bg-murky-700 p-8 shadow-lg">
        <div class="flex flex-col gap-4">
            <div class="text-xl"><h1>Terms and Conditions</h1></div>
            <div class="flex flex-col gap-1">
                <p class="text-xs">Welcome to {{ $config->judul_web }} Shop!</p>
                <p class="text-xs">These terms and conditions outline the rules and regulations for the use of {{ $config->judul_web }} Website, located at <a class="text-primary-500" href="{{ ENV('APP_URL') }}">{{ ENV('APP_URL') }}</a></p>
                <p class="text-xs">By accessing this website we assume you accept these terms and conditions. Do not continue to use {{ $config->judul_web }} if you do not agree to take all of the terms and conditions stated on this page.</p>
                <p class="text-xs">
                    The following terminology applies to these Terms and Conditions, Privacy Statement and Disclaimer Notice and all Agreements: "Client", "You" and "Your" refers to you, the person log on this website and compliant to the
                    Company’s terms and conditions. "The Company", "Ourselves", "We", "Our" and "Us", refers to our Company. "Party", "Parties", or "Us", refers to both the Client and ourselves. All terms refer to the offer, acceptance and
                    consideration of payment necessary to undertake the process of our assistance to the Client in the most appropriate manner for the express purpose of meeting the Client's needs in respect of provision of the Company's
                    stated services, in accordance with and subject to, prevailing law of Netherlands. Any use of the above terminology or other words in the singular, plural, capitalization and/or he/she or they, are taken as
                    interchangeable and therefore as referring to same.
                </p>
                <h3 class="mt-4 underline underline-offset-2"><strong>Cookies</strong></h3>
                <p class="text-xs">We employ the use of cookies. By accessing {{ $config->judul_web }}, you agreed to use cookies in agreement with the {{ $config->judul_web }}'s Privacy Policy.</p>
                <p class="text-xs">
                    Most interactive websites use cookies to let us retrieve the user’s details for each visit. Cookies are used by our website to enable the functionality of certain areas to make it easier for people visiting our website.
                    Some of our affiliate/advertising partners may also use cookies.
                </p>
                <h3 class="mt-4 underline underline-offset-2"><strong>License</strong></h3>
                <p class="text-xs">
                    Unless otherwise stated, {{ $config->judul_web }} and/or its licensors own the intellectual property rights for all material on pisceskooky. All intellectual property rights are reserved. You may access this from {{ $config->judul_web }} for your own
                    personal use subjected to restrictions set in these terms and conditions.
                </p>
                <p class="text-xs">You must not:</p>
                <ul class="ml-4 list-disc">
                    <li class="text-xs">Republish material from {{ $config->judul_web }}</li>
                    <li class="text-xs">Sell, rent or sub-license material from {{ $config->judul_web }}</li>
                    <li class="text-xs">Reproduce, duplicate or copy material from {{ $config->judul_web }}</li>
                    <li class="text-xs">Redistribute content from {{ $config->judul_web }}</li>
                </ul>
                <p class="text-xs">
                    This Agreement shall begin on the date hereof. Our Terms and Conditions were created with the help of the
                    <a class="text-primary-500" href="https://www.termsandconditionsgenerator.com">Terms And Conditions Generator</a> and the
                    <a class="text-primary-500" href="https://www.generateprivacypolicy.com">Privacy Policy Generator</a>.
                </p>
                <p class="text-xs">
                    Parts of this website offer an opportunity for users to post and exchange opinions and information in certain areas of the website. {{ $config->judul_web }} does not filter, edit, publish or review Comments prior to their presence on
                    the website. Comments do not reflect the views and opinions of {{ $config->judul_web }} ,its agents and/or affiliates. Comments reflect the views and opinions of the person who post their views and opinions. To the extent permitted by
                    applicable laws, {{ $config->judul_web }} shall not be liable for the Comments or for any liability, damages or expenses caused and/or suffered as a result of any use of and/or posting of and/or appearance of the Comments on this
                    website.
                </p>
                <p class="text-xs">{{ $config->judul_web }} reserves the right to monitor all Comments and to remove any Comments which can be considered inappropriate, offensive or causes breach of these Terms and Conditions.</p>
                <p class="text-xs">You warrant and represent that:</p>
                <ul>
                    <li class="text-xs">You are entitled to post the Comments on our website and have all necessary licenses and consents to do so;</li>
                    <li class="text-xs">The Comments do not invade any intellectual property right, including without limitation copyright, patent or trademark of any third party;</li>
                    <li class="text-xs">The Comments do not contain any defamatory, libelous, offensive, indecent or otherwise unlawful material which is an invasion of privacy</li>
                    <li class="text-xs">The Comments will not be used to solicit or promote business or custom or present commercial activities or unlawful activity.</li>
                </ul>
                <p class="text-xs">You hereby grant {{ $config->judul_web }} a non-exclusive license to use, reproduce, edit and authorize others to use, reproduce and edit any of your Comments in any and all forms, formats or media.</p>
                <h3 class="mt-4 underline underline-offset-2"><strong>Hyperlinking to our Content</strong></h3>
                <p class="text-xs">The following organizations may link to our Website without prior written approval:</p>
                <ul>
                    <li class="text-xs">Government agencies;</li>
                    <li class="text-xs">Search engines;</li>
                    <li class="text-xs">News organizations;</li>
                    <li class="text-xs">Online directory distributors may link to our Website in the same manner as they hyperlink to the Websites of other listed businesses; and</li>
                    <li class="text-xs">System wide Accredited Businesses except soliciting non-profit organizations, charity shopping malls, and charity fundraising groups which may not hyperlink to our Web site.</li>
                </ul>
                <p class="text-xs">
                    These organizations may link to our home page, to publications or to other Website information so long as the link: (a) is not in any way deceptive; (b) does not falsely imply sponsorship, endorsement or approval of the
                    linking party and its products and/or services; and (c) fits within the context of the linking party’s site.
                </p>
                <p class="text-xs">We may consider and approve other link requests from the following types of organizations:</p>
                <ul>
                    <li class="text-xs">commonly-known consumer and/or business information sources;</li>
                    <li class="text-xs">dot.com community sites;</li>
                    <li class="text-xs">associations or other groups representing charities;</li>
                    <li class="text-xs">online directory distributors;</li>
                    <li class="text-xs">internet portals;</li>
                    <li class="text-xs">accounting, law and consulting firms; and</li>
                    <li class="text-xs">educational institutions and trade associations.</li>
                </ul>
                <p class="text-xs">
                    We will approve link requests from these organizations if we decide that: (a) the link would not make us look unfavorably to ourselves or to our accredited businesses; (b) the organization does not have any negative
                    records with us; (c) the benefit to us from the visibility of the hyperlink compensates the absence of {{ $config->judul_web }}; and (d) the link is in the context of general resource information.
                </p>
                <p class="text-xs">
                    These organizations may link to our home page so long as the link: (a) is not in any way deceptive; (b) does not falsely imply sponsorship, endorsement or approval of the linking party and its products or services; and
                    (c) fits within the context of the linking party’s site.
                </p>
                <p class="text-xs">
                    If you are one of the organizations listed in paragraph 2 above and are interested in linking to our website, you must inform us by sending an e-mail to {{ $config->judul_web }}. Please include your name, your organization name,
                    contact information as well as the URL of your site, a list of any URLs from which you intend to link to our Website, and a list of the URLs on our site to which you would like to link. Wait 2-3 weeks for a response.
                </p>
                <p class="text-xs">Approved organizations may hyperlink to our Website as follows:</p>
                <ul>
                    <li class="text-xs">By use of our corporate name; or</li>
                    <li class="text-xs">By use of the uniform resource locator being linked to; or</li>
                    <li class="text-xs">By use of any other description of our Website being linked to that makes sense within the context and format of content on the linking party’s site.</li>
                </ul>
                <p class="text-xs">No use of {{ $config->judul_web }}'s logo or other artwork will be allowed for linking absent a trademark license agreement.</p>
                <h3 class="mt-4 underline underline-offset-2"><strong>iFrames</strong></h3>
                <p class="text-xs">Without prior approval and written permission, you may not create frames around our Webpages that alter in any way the visual presentation or appearance of our Website.</p>
                <h3 class="mt-4 underline underline-offset-2"><strong>Content Liability</strong></h3>
                <p class="text-xs">
                    We shall not be hold responsible for any content that appears on your Website. You agree to protect and defend us against all claims that is rising on your Website. No link(s) should appear on any Website that may be
                    interpreted as libelous, obscene or criminal, or which infringes, otherwise violates, or advocates the infringement or other violation of, any third party rights.
                </p>
                <h3 class="mt-4 underline underline-offset-2"><strong>Your Privacy</strong></h3>
                <p class="text-xs">Please read <a href="/id/privacy-policy">Privacy Policy</a></p>
                <h3 class="mt-4 underline underline-offset-2"><strong>Reservation of Rights</strong></h3>
                <p class="text-xs">
                    We reserve the right to request that you remove all links or any particular link to our Website. You approve to immediately remove all links to our Website upon request. We also reserve the right to amen these terms and
                    conditions and it’s linking policy at any time. By continuously linking to our Website, you agree to be bound to and follow these linking terms and conditions.
                </p>
                <h3 class="mt-4 underline underline-offset-2"><strong>Removal of links from our website</strong></h3>
                <p class="text-xs">
                    If you find any link on our Website that is offensive for any reason, you are free to contact and inform us any moment. We will consider requests to remove links but we are not obligated to or so or to respond to you
                    directly.
                </p>
                <p class="text-xs">
                    We do not ensure that the information on this website is correct, we do not warrant its completeness or accuracy; nor do we promise to ensure that the website remains available or that the material on the website is kept
                    up to date.
                </p>
                <h3 class="mt-4 underline underline-offset-2"><strong>Disclaimer</strong></h3>
                <p class="text-xs">To the maximum extent permitted by applicable law, we exclude all representations, warranties and conditions relating to our website and the use of this website. Nothing in this disclaimer will:</p>
                <ul>
                    <li class="text-xs">limit or exclude our or your liability for death or personal injury;</li>
                    <li class="text-xs">limit or exclude our or your liability for fraud or fraudulent misrepresentation;</li>
                    <li class="text-xs">limit any of our or your liabilities in any way that is not permitted under applicable law; or</li>
                    <li class="text-xs">exclude any of our or your liabilities that may not be excluded under applicable law.</li>
                </ul>
                <p class="text-xs">
                    The limitations and prohibitions of liability set in this Section and elsewhere in this disclaimer: (a) are subject to the preceding paragraph; and (b) govern all liabilities arising under the disclaimer, including
                    liabilities arising in contract, in tort and for breach of statutory duty.
                </p>
                <p class="text-xs">As long as the website and the information and services on the website are provided free of charge, we will not be liable for any loss or damage of any nature.</p>
            </div>
        </div>
    </div>
</div>


@include('../footer')


@endsection