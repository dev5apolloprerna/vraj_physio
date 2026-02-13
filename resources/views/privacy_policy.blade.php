<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="UTF-8" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <title>Privacy Policy | Vraj Physio</title>
      <link rel="stylesheet" href="style.css" />
      <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600;700&family=Mulish:wght@300;400;600;700&display=swap" rel="stylesheet">
      <style>
         * {
         margin: 0;
         padding: 0;
         box-sizing: border-box;
         }
         body {
         font-family: 'Mulish', sans-serif;
         color: #333;
         line-height: 1.7;
         background: #f8f9fa;
         }
         /* ---------- HEADER ---------- */
         .header {
         box-shadow: 0 2px 8px rgba(0,0,0,0.08);
         position: sticky;
         top: 0;
         z-index: 100;
         }
         .top-bar {
         background: #005d98;
         color: #fff;
         padding: 10px 20px;
         display: flex;
         justify-content: space-between;
         font-size: 14px;
         }
         .social-icons a {
         margin-left: 10px;
         color: #fff;
         text-decoration: none;
         }
         .nav {
         background: #fff;
         padding: 15px 20px;
         display: flex;
         justify-content: center;
         }
         .nav img {
         max-width: 200px;
         height: auto;
         }
         .info-bar {
         background: #005d98;
         color: #fff;
         display: grid;
         grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
         padding: 15px;
         font-size: 14px;
         text-align: center;
         }
         /* ---------- HERO ---------- */
         .hero {
         height: 350px;
         background: linear-gradient(
         rgba(0,93,152,0.85),
         rgba(0,93,152,0.4)
         ),
         url('images/inner-bg.webp') center/cover no-repeat;
         display: flex;
         align-items: center;
         }
         .hero-content {
         max-width: 1100px;
         margin: auto;
         color: #fff;
         padding: 20px;
         }
         .hero h1 {
         font-size: 42px;
         margin-bottom: 10px;
         }
         .hero p {
         font-size: 18px;
         }
         .hero a {
         color: #fff;
         text-decoration: none;
         }
         .hero a:hover {
         text-decoration: underline;
         }
         /* ---------- CONTENT ---------- */
         .container {
         max-width: 1100px;
         margin: auto;
         padding: 50px 20px;
         background: white;
         border-radius: 10px;
         box-shadow: 0 5px 15px rgba(0,0,0,0.05);
         margin-top: -50px;
         position: relative;
         z-index: 1;
         }
         .content-section {
         margin-bottom: 40px;
         padding-bottom: 30px;
         border-bottom: 1px solid #eee;
         }
         .content-section:last-child {
         border-bottom: none;
         }
         .content h2 {
         color: #005d98;
         font-size: 32px;
         margin-bottom: 30px;
         text-align: center;
         padding-bottom: 10px;
         border-bottom: 2px solid #f0f0f0;
         }
         .content h3 {
         font-size: 22px;
         margin: 25px 0 15px 0;
         color: #005d98;
         }
         .content h4 {
         font-size: 18px;
         margin: 20px 0 10px 0;
         color: #333;
         }
         .content p {
         margin-bottom: 15px;
         }
         .content ul {
         margin-left: 20px;
         margin-bottom: 20px;
         }
         .content li {
         margin-bottom: 8px;
         }
         .highlight-box {
         background: #f0f8ff;
         border-left: 4px solid #005d98;
         padding: 20px;
         margin: 20px 0;
         border-radius: 0 5px 5px 0;
         }
         .contact-info {
         background: #f9f9f9;
         padding: 25px;
         border-radius: 8px;
         margin: 20px 0;
         }
         .contact-info h4 {
         margin-top: 0;
         }
         /* ---------- FOOTER ---------- */
         .footer {
         background: #111;
         color: #ccc;
         padding: 40px 20px;
         margin-top: 50px;
         }
         .footer-content {
         max-width: 1100px;
         margin: auto;
         text-align: center;
         }
         .footer-content img {
         max-width: 220px;
         margin-bottom: 15px;
         }
         .footer-bottom {
         border-top: 1px solid #333;
         margin-top: 30px;
         padding-top: 15px;
         display: flex;
         justify-content: space-between;
         flex-wrap: wrap;
         max-width: 1100px;
         margin-left: auto;
         margin-right: auto;
         }
         .footer-bottom a {
         color: #ccc;
         text-decoration: none;
         }
         .footer-bottom a:hover {
         color: #fff;
         text-decoration: underline;
         }
         /* ---------- RESPONSIVE ---------- */
         @media (max-width: 768px) {
         .hero {
            height: 300px;
         }
         .hero h1 {
            font-size: 32px;
         }
         .container {
            padding: 30px 15px;
            margin-top: -30px;
         }
         .content h2 {
            font-size: 28px;
         }
         .content h3 {
            font-size: 20px;
         }
         .info-bar {
            grid-template-columns: 1fr;
            gap: 10px;
         }
         .footer-bottom {
            flex-direction: column;
            text-align: center;
            gap: 10px;
         }
         }
      </style>
   </head>
   <body>
      <!-- ================= HEADER ================= -->
      <header class="header">
         <div class="top-bar">
            <p>Book your free physio check up appointment today.</p>
            <!--<div class="social-icons">-->
            <!--   <a href="#">FB</a>-->
            <!--   <a href="#">X</a>-->
            <!--   <a href="#">IN</a>-->
            <!--   <a href="#">IG</a>-->
            <!--</div>-->
         </div>
         <div class="nav">
            <img src="https://vrajphysioapp.vrajdentalclinic.com/img/logo.png" alt="Vraj Physio Logo">
         </div>
         <div class="info-bar">
            <div>📍 Sama Savli Rd</div>
            <div>📞 +91 8866 203 090</div>
            <div>✉️ vrajphysiotherapyclinic@gmail.com</div>
            <div>🕒 Mon–Sat 10am–8pm</div>
         </div>
      </header>
      <!-- ================= HERO ================= -->
      <section class="hero">
         <div class="hero-content">
            <h1>Privacy Policy</h1>
            <p><a href="/">Home</a> • Privacy Policy</p>
         </div>
      </section>
      <!-- ================= CONTENT ================= -->
      <main class="container">
         <div class="content">
            <!--<p><strong>Last Updated:</strong> [Date]</p>-->
            
            <div class="content-section">
               <h2>1. Introduction</h2>
               <p>At Vraj Physio ("we," "our," or "us"), we are committed to protecting the privacy and security of your personal information. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website [www.vrajphysio.com], book appointments, receive our services, or otherwise interact with us. This includes sensitive health information, which we treat with the highest standards of confidentiality.</p>
            </div>

            <div class="content-section">
               <h2>2. Information We Collect</h2>
               <p>We collect information to provide you with the best possible physiotherapy care and manage our relationship with you.</p>
               
               <h3>A. Personal Information You Provide:</h3>
               <ul>
                  <li><strong>Contact & Identity:</strong> Name, date of birth, address, email address, phone number, emergency contact details.</li>
                  <li><strong>Health Information:</strong> Medical history, current conditions, injury details, treatment notes, assessment results, progress reports, referral letters from doctors, and insurance information (where applicable).</li>
                  <li><strong>Administrative:</strong> Appointment bookings, payment details (processed securely via our payment partners), feedback, and communications with us.</li>
               </ul>

               <h3>B. Information Collected Automatically (Website):</h3>
               <ul>
                  <li><strong>Technical Data:</strong> IP address, browser type, device information, and operating system.</li>
                  <li><strong>Usage Data:</strong> Pages visited on our website, time spent on pages, referring website, and how you navigated our site (via cookies and similar technologies).</li>
               </ul>
            </div>

            <div class="content-section">
               <h2>3. How We Use Your Information</h2>
               <p>We use your information for legitimate business and healthcare purposes, including:</p>
               <ul>
                  <li><strong>To Provide Healthcare:</strong> To assess, diagnose, plan, and deliver your physiotherapy treatment.</li>
                  <li><strong>Administration:</strong> To schedule and manage appointments, process payments, and handle insurance claims.</li>
                  <li><strong>Communication:</strong> To send you appointment reminders, follow-up instructions, exercise plans, and respond to your inquiries. <strong>We will never use your health information for marketing without your explicit, separate consent.</strong></li>
                  <li><strong>Improvement:</strong> To improve our website, services, and patient care (using anonymized data where possible).</li>
                  <li><strong>Legal & Compliance:</strong> To comply with our legal and regulatory obligations as healthcare providers.</li>
               </ul>
            </div>

            <div class="content-section">
               <h2>4. Legal Basis for Processing (GDPR & Indian Context)</h2>
               <p>We process your personal information based on:</p>
               <ul>
                  <li><strong>Contractual Necessity:</strong> To fulfill our agreement to provide you with treatment.</li>
                  <li><strong>Legal Obligation:</strong> To maintain mandatory clinical records as required by law.</li>
                  <li><strong>Vital Interests:</strong> To protect your vital health interests.</li>
                  <li><strong>Consent:</strong> For specific, optional purposes like sending marketing newsletters (you can opt-out anytime).</li>
               </ul>
            </div>

            <div class="content-section">
               <h2>5. How We Share Your Information</h2>
               <p>Your trust is paramount. We do <strong>not</strong> sell your personal or health data.</p>
               <p>We may share your information <strong>only</strong> in these limited circumstances:</p>
               <ul>
                  <li><strong>With Your Consent:</strong> For example, with your referring doctor, another healthcare specialist, or your insurance provider.</li>
                  <li><strong>For Legal Reasons:</strong> If required by law, court order, or to protect the vital interests of you or another person.</li>
                  <li><strong>Service Providers:</strong> With trusted third parties who work on our behalf under strict confidentiality agreements (e.g., our secure patient management software provider, payment processors, IT support). They are prohibited from using your information for any other purpose.</li>
               </ul>
            </div>

            <div class="content-section">
               <h2>6. Data Retention</h2>
               <p>We retain your personal and health records in accordance with legal and professional obligations (typically for a minimum period as prescribed by local health regulations, e.g., 5-8 years after your last visit, or longer for minors). After this period, records are securely destroyed.</p>
            </div>

            <div class="content-section">
               <h2>7. Your Rights & Choices</h2>
               <p>Depending on your location, you may have the right to:</p>
               <ul>
                  <li><strong>Access</strong> the personal information we hold about you.</li>
                  <li><strong>Correct</strong> inaccurate or incomplete data.</li>
                  <li><strong>Request Deletion</strong> of your data under certain conditions (note: we are legally required to retain health records for a mandated period).</li>
                  <li><strong>Restrict or Object</strong> to certain processing.</li>
                  <li><strong>Data Portability</strong> (to receive your data in a structured format).</li>
                  <li><strong>Opt-out of Marketing</strong> communications at any time (use the "unsubscribe" link or contact us).</li>
               </ul>
               <p>To exercise these rights, please contact us using the details below.</p>
            </div>

            <div class="content-section">
               <h2>8. Cookies</h2>
               <p>Our website uses cookies to enhance functionality and analyze traffic. You can control cookies through your browser settings. Disabling cookies may affect your website experience.</p>
            </div>

            <div class="content-section">
               <h2>9. Security</h2>
               <p>We implement appropriate technical and organizational measures (like encryption, access controls, and secure storage) to protect your sensitive information from unauthorized access, disclosure, alteration, or destruction.</p>
            </div>

            <div class="content-section">
               <h2>10. Third-Party Links</h2>
               <p>Our website may contain links to other sites (e.g., health information resources). We are not responsible for the privacy practices of these external sites.</p>
            </div>

            <div class="content-section">
               <h2>11. Children's Privacy</h2>
               <p>Our services are directed to individuals over the age of 18. We do not knowingly collect information from minors without parental/guardian consent.</p>
            </div>

            <div class="content-section">
               <h2>12. Contact Us</h2>
               <p>If you have any questions, concerns, or requests regarding this Privacy Policy or your data, please contact:</p>
               <div class="contact-info">
                  <h4>Vraj Physio</h4>
                  <p>📍 Sama Savli Rd</p>
                  <p>📞 +91 8866 203 090</p>
                  <p>✉️ vrajphysiotherapyclinic@gmail.com</p>
               </div>
            </div>

            <div class="content-section">
               <h2>13. Updates to This Policy</h2>
               <p>We may update this policy periodically. The updated version will be posted on our website with a revised "Last Updated" date. We encourage you to review it occasionally.</p>
            </div>
         </div>
      </main>
      <!-- ================= FOOTER ================= -->
      <footer class="footer">
         <div class="footer-content">
            <img src="https://vrajphysioapp.vrajdentalclinic.com/img/logo.png" alt="Vraj Physio Logo">
            <p>
               Our patients leave our clinic feeling happy and satisfied with the
               treatment and service we provide.
            </p>
         </div>
         <div class="footer-bottom">
            <p>© 2025 vrajphysioapp.vrajdentalclinic.com. All rights reserved.</p>
            <a href="\privacy-policy">Privacy Policy</a>
         </div>
      </footer>
   </body>
</html>