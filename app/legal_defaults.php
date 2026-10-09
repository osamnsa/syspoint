<?php
/**
 * Starting text for the legal pages (Privacy Policy, Returns & Refunds,
 * Terms of Use). Staff edit them in Admin → Website; these are only the
 * defaults. Placeholders in {braces} fill in from Business details when the
 * page is shown — see legal_tokens() in site_content.php.
 *
 * Written for a Nigerian business (NDPA 2023, FCCPA 2018). Have a lawyer
 * review before relying on it.
 */

declare(strict_types=1);

const LEGAL_DEFAULT_PRIVACY = <<<'HTML'
<p>This policy explains what personal information {company} (“Syspoint”, “we”, “us”) collects through this website, our Gadget Store and Syspoint Hub, why we collect it, and the choices you have. We process personal data in line with the Nigeria Data Protection Act 2023 (NDPA).</p>
<h2>Who we are</h2>
<p>{company} runs this website ({website}), the Gadget Store and Syspoint Hub at {address}. We decide how your personal data is used, so we are its “data controller”. For anything about your data, email <a href="mailto:{email}">{email}</a>{phone_sentence}.</p>
<h2>What we collect</h2>
<ul>
<li><strong>When you order online:</strong> your name, email, phone number, delivery address, what you bought and how much you paid. Card and bank details are entered on Paystack’s secure page — we never see or store your full card number.</li>
<li><strong>When you book a gaming room:</strong> your name, email, phone number, the room, date and time.</li>
<li><strong>When you contact us or send a Software Clinic request:</strong> your name, business name, email, phone number and what you write to us.</li>
<li><strong>In store, at the Hub and in training:</strong> the details you give us for receipts, warranty records, bookings, course enrolment, payments and internship records.</li>
<li><strong>Automatically:</strong> one essential cookie (see “Cookies” below) and our server’s security logs — IP address, browser type, pages requested and the time.</li>
</ul>
<p>We don’t ask for sensitive information (such as health or religious beliefs) and ask you not to send it.</p>
<h2>Why we use it</h2>
<ul>
<li><strong>To provide what you asked for</strong> — processing and delivering orders, warranties and repairs, confirming bookings, enrolling you on a course, quoting for and delivering software work, and answering your messages (performance of a contract, or steps you asked for before one).</li>
<li><strong>To run the business safely</strong> — preventing fraud and misuse, keeping the website secure and improving our service (our legitimate interests, which we balance against your rights).</li>
<li><strong>To meet the law</strong> — keeping tax, accounting and consumer-protection records (legal obligation).</li>
<li><strong>To tell you about offers and events</strong> — only if you have agreed to it (consent). You can withdraw consent at any time and we will stop. Following our Telegram channel is your own choice; we never add you to it.</li>
</ul>
<h2>Who we share it with</h2>
<p>We never sell your personal data. We share only what is needed with:</p>
<ul>
<li><strong>Paystack</strong>, to take payments;</li>
<li><strong>delivery partners</strong> — your name, phone number and address, to bring your order;</li>
<li><strong>manufacturers and authorised service centres</strong>, when you make a warranty claim;</li>
<li><strong>the companies that host our website and email</strong>, and the messaging tool our staff use to be told about new orders and requests;</li>
<li><strong>professional advisers and public authorities</strong> where the law requires it, or to protect our rights or someone’s safety.</li>
</ul>
<p>Each of them may use your data only for the job we give them and must keep it secure.</p>
<h2>Transfers outside Nigeria</h2>
<p>Some of these providers store data on servers outside Nigeria. Where that happens we rely on the safeguards the NDPA allows, such as the country having adequate data protection, or contractual protections with the provider.</p>
<h2>How long we keep it</h2>
<ul>
<li>Orders, receipts, invoices and payment records: as long as tax and accounting law requires — generally six years.</li>
<li>Warranty records: until the warranty ends, plus one year.</li>
<li>Enquiries, Software Clinic requests and booking requests that don’t lead to a sale: up to two years after we last hear from you.</li>
<li>Training and internship records: for as long as we may need to confirm your attendance or certificate.</li>
<li>Server security logs: up to 90 days.</li>
</ul>
<p>After that we delete it or make it anonymous.</p>
<h2>Your rights</h2>
<p>Under the NDPA you can ask us to:</p>
<ul>
<li>give you a copy of the personal data we hold about you;</li>
<li>correct anything that is wrong or incomplete;</li>
<li>delete your data, or restrict how we use it;</li>
<li>stop using it for a purpose you object to, including marketing;</li>
<li>give you your data in a common electronic format, or send it to someone else;</li>
<li>withdraw any consent you gave.</li>
</ul>
<p>Email <a href="mailto:{email}">{email}</a>. We may need to check your identity first, and we aim to reply within 30 days. Some records we must keep by law even if you ask us to delete them; if so we will tell you. If you are unhappy with how we handle your data you can complain to the Nigeria Data Protection Commission (<a href="https://ndpc.gov.ng">ndpc.gov.ng</a>) — but please talk to us first so we can try to put it right.</p>
<h2>Cookies</h2>
<p>We use a single essential cookie that remembers your cart and keeps forms secure. It is deleted when you close your browser. We don’t use advertising or tracking cookies. Our fonts are loaded from Google Fonts, so your browser connects to Google’s servers, which can see your IP address.</p>
<h2>Keeping it safe</h2>
<p>The website uses an encrypted (HTTPS) connection, payments go through Paystack, and only staff who need your details can see them. No system is perfectly secure; if a breach puts your rights at risk we will tell you and the Nigeria Data Protection Commission as the law requires.</p>
<h2>Children</h2>
<p>Our online shop is meant for adults. If you are under 18, a parent or guardian should place orders and provide the details for gaming bookings or training enrolment on your behalf.</p>
<h2>Changes to this policy</h2>
<p>We may update this policy when our services or the law change. The date at the top shows the latest version; for important changes we will also put a notice on the website.</p>
HTML;

const LEGAL_DEFAULT_RETURNS = <<<'HTML'
<p>We want you to be happy with everything you buy from {company}, online or at our Gadget Store. This policy explains how returns, exchanges and refunds work. It doesn’t take away any of your rights under the Federal Competition and Consumer Protection Act 2018.</p>
<h2>Faulty, damaged or wrong items</h2>
<p>If your item arrives damaged, doesn’t work, or isn’t what you ordered, tell us within <strong>{return_days} days</strong> of delivery or collection. We will repair it, replace it, or give you a full refund — including any delivery charge — and we cover the cost of getting it back to us.</p>
<p>Please check your order when it arrives, and tell the delivery rider or our staff straight away if the packaging is visibly damaged.</p>
<h2>Changed your mind?</h2>
<p>You can return an item within <strong>{return_days} days</strong> of delivery or collection for an exchange or a refund if it is:</p>
<ul>
<li>unused, and in its original, undamaged packaging;</li>
<li>complete, with all accessories, manuals, cables and any free gifts;</li>
<li>returned with its receipt or order number.</li>
</ul>
<p>For change-of-mind returns you pay the cost of bringing or sending the item back, and the original delivery charge isn’t refunded.</p>
<h2>Items we can’t take back (unless faulty)</h2>
<ul>
<li>software, licences and digital codes once the seal is broken or the code has been revealed or activated;</li>
<li>earphones, headsets and other items whose hygiene seal has been opened;</li>
<li>items configured, upgraded or ordered specially for you;</li>
<li>consumables such as ink and toner once opened;</li>
<li>items damaged after delivery by misuse, liquid, drops or power surges.</li>
</ul>
<h2>Warranty</h2>
<p>Many products come with a manufacturer’s warranty, and some with a Syspoint warranty; the period is shown on the product page or your receipt. A warranty covers manufacturing faults. It doesn’t cover physical or liquid damage, damage from power surges, normal wear and tear, or repairs and changes made by anyone other than us or an authorised service centre.</p>
<p>To make a claim, bring the item and your receipt to the Gadget Store ({store_suite}, {address}). We will check it, tell you what we found — usually within five working days — and then repair or replace it, or send it to the manufacturer’s service centre.</p>
<h2>How to return something</h2>
<ol>
<li>Contact us at <a href="mailto:{email}">{email}</a>{phone_sentence} with your order number and the reason for the return.</li>
<li>Bring the item to the Gadget Store, or arrange with us for it to be collected.</li>
<li>We inspect it and confirm by email or phone whether the return is accepted.</li>
</ol>
<p><strong>Before returning a computer, phone or storage device,</strong> back up your files and remove your passwords and accounts. We aren’t responsible for data left on returned devices.</p>
<h2>Refunds</h2>
<p>Once we have received and checked the item, we refund you within <strong>{refund_days} working days</strong>, to the payment method you used. Card payments made through Paystack go back to the same card; your bank may take a few more days to show it. Cash and transfer payments made in store are refunded by bank transfer to an account in the buyer’s name.</p>
<h2>Gaming room bookings</h2>
<p>Room bookings are requests until we confirm them. If you can’t make it, let us know at least two hours before your slot and we will move or cancel it free of charge. Any amount paid for a session you miss without telling us isn’t refundable.</p>
<h2>Training courses</h2>
<p>If you cancel before a course starts, we refund the course fee in full, less any non-refundable registration fee stated when you enrolled. Once a course has started, fees aren’t refundable, but you can move once to a later intake if there is space. If we cancel or postpone a course, you choose between a full refund and a place on the new dates.</p>
<h2>Events</h2>
<p>If we cancel an event, or postpone it and the new date doesn’t suit you, we refund your entry fee in full. Otherwise entry fees aren’t refundable, but you may give your place to someone else — just tell us their name.</p>
<h2>Software Clinic and consulting</h2>
<p>Software and consulting work follows the quote or agreement for that project. Deposits pay for work already started and aren’t refundable unless the agreement says otherwise.</p>
HTML;

const LEGAL_DEFAULT_TERMS = <<<'HTML'
<p>These terms apply when you use this website ({website}) or buy from {company} (“Syspoint”, “we”, “us”). By using the website or placing an order, you agree to them. Please also read our <a href="/privacy-policy">Privacy Policy</a> and <a href="/returns-policy">Returns &amp; Refunds</a> policy.</p>
<h2>Who we are</h2>
<p>{company}, {address}. Email <a href="mailto:{email}">{email}</a>{phone_sentence}.</p>
<h2>Using the website</h2>
<p>You may use the website to browse, shop, book and contact us. You agree not to:</p>
<ul>
<li>break the law or use the website for fraud;</li>
<li>try to get into parts of the website or systems you aren’t meant to, or interfere with how it works;</li>
<li>copy the website’s content in bulk or use automated tools to collect it;</li>
<li>send anything harmful, offensive or untrue through our forms.</li>
</ul>
<p>We may suspend access to anyone who misuses the website. We try to keep the website available and accurate, but can’t promise it will always be uninterrupted or free of errors.</p>
<h2>Products and prices</h2>
<p>Prices are in Nigerian Naira (₦). Photos are for illustration, and specifications come from manufacturers; small differences in colour or packaging can occur. If a price on the website is clearly wrong, we will contact you before your order goes ahead. If you choose not to continue, we cancel the order and refund you in full.</p>
<h2>Orders and payment</h2>
<p>Placing an order is an offer to buy. A contract is formed when your payment is confirmed and we accept the order. We may decline or cancel an order — for example if an item is out of stock or a payment looks fraudulent — and if we do, we refund you in full. Online payments are processed securely by Paystack.</p>
<h2>Delivery and collection</h2>
<p>We confirm delivery times, and any delivery charge, with you after you order. The item becomes your responsibility once it is delivered to you or collected. Please check your order when it arrives.</p>
<h2>Returns and warranty</h2>
<p>Returns, refunds and warranty claims are covered by our <a href="/returns-policy">Returns &amp; Refunds</a> policy.</p>
<h2>Syspoint Hub — gaming lounge</h2>
<ul>
<li>A booking is a request until we confirm it by email or phone.</li>
<li>Please treat consoles, VR headsets, controllers and furniture with care. You may be asked to pay for damage caused deliberately or carelessly.</li>
<li>We follow game age ratings (such as PEGI). Children must be supervised by a parent or guardian, and staff may ask for proof of age.</li>
<li>The free internet is for reasonable use. Don’t use it for anything illegal, and don’t try to bypass our network controls.</li>
<li>We may refuse entry or ask someone to leave if they put others, staff or equipment at risk.</li>
</ul>
<h2>Training and internships</h2>
<p>Course content, schedules and fees are as described when you enrol. A certificate is issued when you meet the course’s attendance and assessment requirements. Students and interns are expected to behave respectfully. We may remove anyone who seriously or repeatedly disrupts a class, without a refund.</p>
<h2>Software Clinic and consulting</h2>
<p>Software and consulting projects are governed by the quote or agreement for that project. Where it differs from these terms, the quote or agreement wins.</p>
<h2>Events</h2>
<p>Event details can change. If an event is moved or cancelled we will tell you, and refunds follow our Returns &amp; Refunds policy. We may take photos and videos at events to share on our channels — let our staff know if you would rather not appear.</p>
<h2>Intellectual property</h2>
<p>The website’s text, design, logos and photos belong to Syspoint or are used with permission. You may view and share pages for personal use, but not copy or reuse them for business without our written permission. Product names and logos belong to their owners.</p>
<h2>Links to other sites</h2>
<p>The website links to services run by others, such as Paystack, Telegram and Google Maps. We aren’t responsible for their content or how they handle your data.</p>
<h2>Our responsibility to you</h2>
<p>Nothing in these terms limits our liability where the law doesn’t allow it to be limited — including for death or personal injury caused by our negligence, for fraud, or your rights under the Federal Competition and Consumer Protection Act 2018. Otherwise:</p>
<ul>
<li>we aren’t liable for losses we couldn’t reasonably have foreseen, or for indirect losses such as lost profits or business;</li>
<li>we aren’t liable for loss of data — please keep backups, especially before repairs, upgrades or returns;</li>
<li>our total liability for any order or service is limited to the amount you paid for it.</li>
</ul>
<h2>Changes to these terms</h2>
<p>We may update these terms from time to time. The version on the website when you place an order is the one that applies to that order.</p>
<h2>Law and disputes</h2>
<p>These terms are governed by the laws of the Federal Republic of Nigeria. If something goes wrong, please contact us first — most problems can be settled quickly. Any dispute we can’t resolve together will be decided by the courts of the Federal Capital Territory, Abuja.</p>
HTML;
