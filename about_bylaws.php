<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_configuration.php'; // provides $db (mysqli)
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Bylaws – Learn and Help</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link href="css/main.css?v=2025-08-22a" rel="stylesheet">

    <style>
        :root { --accent: #99D930; }

        body {
            margin: 0;
            font-family: 'Roboto', sans-serif;
            background: #f8f8f8;
            color: #252525;
        }

        /* ── Banner ── */
        .banner-wrapper {
            width: 100vw;
            left: 50%;
            margin-left: -50vw;
            height: 220px;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
            position: relative;
        }
        .banner-wrapper img {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;
        }
        .banner-title {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            margin: 0;
            font-family: 'Roboto', sans-serif;
            font-size: 3em;
            font-weight: 900;
            color: #99d930;
            text-shadow: 0 2px 16px rgba(0,0,0,0.44);
            letter-spacing: 1px;
            z-index: 2;
            white-space: nowrap;
        }
        .banner-subtitle {
            position: absolute;
            bottom: 18px;
            left: 50%;
            transform: translateX(-50%);
            margin: 0;
            font-size: 1.1em;
            color: #fff;
            text-shadow: 0 1px 6px rgba(0,0,0,.5);
            z-index: 2;
            white-space: nowrap;
        }

        /* ── Page wrapper ── */
        .page-wrap {
            max-width: 1000px;
            margin: 40px auto 60px auto;
            padding: 0 20px;
        }

        /* ── Draft watermark ── */
        .draft-watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 8rem;
            font-weight: 900;
            color: rgba(153, 217, 48, 0.08);
            z-index: 0;
            pointer-events: none;
            user-select: none;
        }

        /* ── Draft notice ── */
        .draft-notice {
            background: #fff8e1;
            border: 1.5px solid #ffcc02;
            color: #856404;
            padding: 13px 18px;
            border-radius: 9px;
            font-weight: 700;
            margin-bottom: 24px;
            font-size: .95em;
        }

        /* ── Card ── */
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 6px 32px rgba(80,120,180,0.09);
            border: 2px solid #99d930;
            padding: 40px;
            margin-bottom: 28px;
            position: relative;
            z-index: 1;
        }

        /* ── TOC ── */
        .bylaws-toc {
            background: #f8fbe9;
            border: 1.5px solid #cde8a0;
            border-radius: 10px;
            padding: 22px 26px;
            margin: 28px 0;
        }
        .bylaws-toc h3 {
            margin: 0 0 14px 0;
            font-size: 1.1em;
            font-weight: 900;
            color: #274606;
        }
        .bylaws-toc ul {
            list-style: none;
            padding-left: 0;
            margin: 0;
        }
        .bylaws-toc li {
            margin: 8px 0;
            padding-left: 22px;
            position: relative;
            font-size: .97em;
            color: #333;
        }
        .bylaws-toc li::before {
            content: "▶";
            color: #99d930;
            position: absolute;
            left: 0;
            font-size: .8em;
            top: 3px;
        }

        /* ── Typography ── */
        .content-section h2 {
            font-size: 2em;
            font-weight: 900;
            margin: 0 0 6px 0;
            color: #252525;
            text-align: center;
        }
        .content-section .version-tag {
            text-align: center;
            font-style: italic;
            color: #888;
            font-size: .9em;
            margin-bottom: 24px;
        }
        .content-section h3 {
            font-size: 1.4em;
            font-weight: 900;
            margin: 32px 0 12px 0;
            color: #252525;
        }
        .content-section h4 {
            font-size: 1.1em;
            font-weight: 700;
            margin: 22px 0 8px 0;
            color: #444;
        }
        .content-section p {
            font-size: 1.05em;
            line-height: 1.7;
            color: #444;
            margin: 0 0 18px 0;
        }
        .content-section ul {
            font-size: 1.05em;
            line-height: 1.7;
            color: #444;
            margin: 0 0 18px 0;
            padding-left: 22px;
        }
        .content-section li { margin-bottom: 8px; }

        .article-number { color: #99d930; font-weight: 900; }

        /* ── Contact info box ── */
        .contact-info {
            background: #f8fbe9;
            border: 1.5px solid #cde8a0;
            border-radius: 8px;
            padding: 18px 22px;
            margin: 18px 0;
        }
        .contact-info h4 { margin: 0 0 8px 0; color: #274606; }
        .contact-info p  { margin: 0; }

        /* ── Board member cards ── */
        .board-member {
            background: #f8fbe9;
            border-left: 4px solid #99d930;
            border-radius: 0 8px 8px 0;
            padding: 14px 18px;
            margin: 10px 0;
            font-size: 1em;
            color: #333;
            line-height: 1.6;
        }

        /* ── TOC note at bottom ── */
        .bylaws-note {
            background: #f8fbe9;
            border: 1.5px solid #cde8a0;
            border-radius: 10px;
            padding: 16px 20px;
            font-size: .93em;
            color: #274606;
            margin-top: 24px;
        }

        @media (max-width: 700px) {
            .card { padding: 22px 16px; }
            .content-section h2 { font-size: 1.6em; }
            .content-section h3 { font-size: 1.2em; }
            .draft-watermark { font-size: 4rem; }
            .banner-title { font-size: 2em; }
        }
    </style>
</head>
<body>

<div class="draft-watermark">DRAFT</div>

<?php include 'show-navbar.php'; show_navbar(); ?>

<!-- ── Banner ── -->
<div class="banner-wrapper">
    <img src="images/banner_images/Admin/block-pattern.jpg" alt="Bylaws banner">
    <h1 class="banner-title">Bylaws</h1>
    <p class="banner-subtitle">The governing rules and procedures of Learn and Help</p>
</div>

<div class="page-wrap">

    <div class="card">
        <div class="content-section">

            <div class="draft-notice">
                ⚠️ <strong>DRAFT VERSION:</strong> These bylaws are currently in draft form (Version D) and subject to revision.
            </div>

            <h2>Learn and Help Bylaws</h2>
            <p class="version-tag">Version D – Draft &nbsp;|&nbsp; September 2025</p>

            <!-- Table of Contents -->
            <div class="bylaws-toc">
                <h3>Table of Contents</h3>
                <ul>
                    <li>Article I – Name and Purpose</li>
                    <li>Article II – Membership</li>
                    <li>Article III – Classes</li>
                    <li>Article IV – Causes</li>
                    <li>Article V – Board of Directors</li>
                    <li>Article VI – Meetings</li>
                    <li>Article VII – Officers</li>
                    <li>Article VIII – Finances</li>
                    <li>Article IX – Amendments</li>
                </ul>
            </div>

            <!-- Article I -->
            <h3><span class="article-number">Article I</span> – Name and Purpose</h3>

            <h4>Section 1.1 – Name</h4>
            <p>The name of the organization shall be "Learn and Help."</p>

            <h4>Section 1.2 – Registered Address</h4>
            <p>The registered address of Learn and Help is 5736 Pond Ct., Shoreview, MN 55126.</p>

            <div class="contact-info">
                <h4>Contact Information</h4>
                <p>For all organizational inquiries, please contact Learn and Help at
                   +1 651 276 4671 or
                   <a href="mailto:siva.jasthi@gmail.com" style="color:#4a8500;">siva.jasthi@gmail.com</a>.
                </p>
            </div>

            <h4>Section 1.3 – Purpose</h4>
            <p>The organization is organized as a nonprofit corporation under the laws of Minnesota:</p>
            <ul>
                <li>Its main goal is to educate students in computer science, programming, and computational thinking, and related courses, conducted via Zoom.</li>
                <li>Instructors shall determine the course curriculum, schedule, fees, delivery logistics, and any other relevant educational content and structure for each course.</li>
                <li>Instructors or Teaching Assistants are volunteers and do not receive any compensation.</li>
                <li>After deducting any nominal course delivery expenses (such as Zoom, Google tools, etc.), remaining proceeds are used to support charitable projects such as setting up school libraries, providing school supplies, and funding art projects.</li>
                <li>Primary Funding Source: Course fees paid by students are the primary funding source for the organization's charitable initiatives.</li>
                <li>Proceeds Usage: The organization strives to spend all proceeds collected in an academic year within that year or the following year to support various causes; accumulating funds is discouraged.</li>
            </ul>

            <!-- Article II -->
            <h3><span class="article-number">Article II</span> – Membership</h3>

            <h4>Section 2.1 – Non-Membership Corporation</h4>
            <p>Learn and Help shall have no formal membership structure.</p>

            <h4>Section 2.2 – Organizational Roles</h4>
            <ul>
                <li><strong>Board:</strong> These members formulate the governing policies and update the bylaws as required.</li>
                <li><strong>Instructors:</strong> Individuals responsible for offering classes. Instructors are not paid for their roles.</li>
                <li><strong>Teaching Assistants:</strong> Typically middle and high school students who assist instructors with instructional duties and operational tasks. Teaching Assistants for a class are selected by the instructors teaching that class.</li>
                <li><strong>Students:</strong> Typically middle and high school students. At the discretion of instructors, adult learners and parents may also be permitted to register for classes.</li>
                <li><strong>Parents:</strong> Parents support their students and are encouraged to passively learn alongside them.</li>
                <li><strong>Industry Experts:</strong> The organization seeks the help of industry experts for guest talks and to review students' projects and presentations. Any honorarium the board decides to extend to the expert is applied to a cause as chosen by the invited industry expert.</li>
                <li><strong>LEAD Council (Learn and Help Emerging Advisors &amp; Directors):</strong> The LEAD Council shall consist of senior students who have successfully completed at least two courses with Learn and Help and are currently enrolled in the program. Members of the LEAD Council serve in dual roles as Advisors and Directors, acting as role models for their peers.</li>
            </ul>

            <!-- Article III -->
            <h3><span class="article-number">Article III</span> – Classes</h3>
            <ul>
                <li>Class offerings are typically in the domain of computer science, programming, and computational thinking.</li>
                <li>Based on the availability of instructors and requests from the students, courses in other domains (economics, AP classes) will be offered. Though instructors can propose new offerings, they must be approved by the board.</li>
                <li>The instructor can set the minimum enrollment needed for the class to continue.</li>
                <li>Regular session classes are run from September to May.</li>
                <li>Summer classes may be offered in June, July, and August.</li>
            </ul>

            <!-- Article IV -->
            <h3><span class="article-number">Article IV</span> – Causes</h3>

            <h4>Section 4.1 – Supported Projects</h4>
            <p>Learn and Help supports various charitable projects, including establishing school libraries in India, providing educational supplies, promoting the arts, preserving and sharing Indian cultural heritage, and offering scholarships.</p>

            <h4>Section 4.2 – Cause Selection</h4>
            <p>Instructors, Teaching Assistants, parents, and students are encouraged to recommend causes for organizational support. All recommendations must be secular and are subject to review and approval by the Board of Directors.</p>

            <!-- Article V -->
            <h3><span class="article-number">Article V</span> – Board of Directors</h3>

            <h4>Section 5.1 – General Powers</h4>
            <p>The Board of Directors shall manage the affairs of the organization, establish policies, and ensure alignment with Learn and Help's mission to empower minds and inspire generosity through educational and charitable activities.</p>

            <h4>Section 5.2 – Number of Directors</h4>
            <p>The Board of Directors shall initially consist of four members, expanding to seven by 2027:</p>

            <div class="board-member">
                <strong>President:</strong> Presides over meetings, sets agendas, and represents the organization publicly. Until a Director of Finance is appointed, the President shall oversee financial responsibilities.
            </div>
            <div class="board-member">
                <strong>Director of Student Achievement:</strong> Responsible for encouraging, guiding, and supporting students in pursuing certifications, competitions, awards, and other forms of academic and professional recognition.
            </div>
            <div class="board-member">
                <strong>Director of School Libraries:</strong> Oversees the selection, planning, execution, establishment, and sustainment of school libraries supported by Learn and Help.
            </div>
            <div class="board-member">
                <strong>Director of Administration:</strong> Responsible for developing operating procedures, documenting board meeting minutes, maintaining and organizing organizational records, and managing board communication.
            </div>
            <div class="board-member">
                <strong>Director of Helping:</strong> Manages charitable initiatives, supports the selection and oversight of causes, and ensures alignment of projects with the organization's values of generosity and service.
            </div>

            <h4>Section 5.3 – Terms and Elections</h4>
            <ul>
                <li><strong>Term Length:</strong> Director of Student Leadership (LEAD Council) shall serve a term of one year. All other board members shall serve a term of three years.</li>
                <li><strong>Term Limits:</strong> Board members may serve a maximum of two consecutive terms (totaling up to six years). After two consecutive terms, directors must step down for at least one year before being eligible for re-election.</li>
                <li><strong>Board Vacancies:</strong> In the event of a vacancy, a new director may be elected by a majority vote of the remaining board members to complete the term of the departing director.</li>
            </ul>

            <!-- Article VI -->
            <h3><span class="article-number">Article VI</span> – Meetings</h3>
            <ul>
                <li><strong>Annual Meetings:</strong> An annual meeting of the Board shall be held on Zoom at a designated time to discuss organizational progress, review recommended causes, and set strategic directions.</li>
                <li><strong>Special Meetings:</strong> Special meetings may be called as necessary and will also be conducted on Zoom.</li>
                <li><strong>Notice of Meetings:</strong> Notice of each meeting, including the Zoom link, shall be given to each director not less than one week in advance.</li>
            </ul>

            <!-- Article VII -->
            <h3><span class="article-number">Article VII</span> – Officers</h3>

            <h4>Current Officers</h4>
            <div class="board-member"><strong>President:</strong> Siva Jasthi (Minneapolis, MN, USA)</div>
            <div class="board-member"><strong>Director of Student Achievements:</strong> Ishana Didwania (West Lafayette, IN, USA)</div>
            <div class="board-member"><strong>Director of School Libraries:</strong> Dr. C.A. Prasad (Ongole, AP, India)</div>
            <div class="board-member"><strong>Director of Helping:</strong> Varma Alluri (Irvine, CA, USA)</div>
            <div class="board-member"><strong>Director of Administration:</strong> Venkat (Bobby) Kodali (Woodbury, MN, USA)</div>

            <!-- Article VIII -->
            <h3><span class="article-number">Article VIII</span> – Finances</h3>
            <ul>
                <li><strong>Use of Funds:</strong> All funds shall be used solely to support Learn and Help's mission of (a) empowering minds and (b) inspiring generosity.</li>
                <li><strong>Course Fees:</strong> The fee for each class is decided by the instructor and approved by the Board.</li>
                <li><strong>Payments:</strong> Board Members and Instructors are not paid for their services. Teaching Assistants may be paid at the discretion of the instructors.</li>
                <li><strong>Donations:</strong> The organization accepts donations from parents and other organizations and can honor the requests of donors to support causes, provided those causes align with the organization's mission.</li>
                <li><strong>Usage of Proceeds:</strong> The organization strives to spend all proceeds collected in an academic year within that year or the following year to support various causes; accumulating funds is discouraged.</li>
                <li><strong>Selection of Charitable Causes:</strong> Education, Arts, Culture and Empowerment are some areas of focus for charitable causes.</li>
            </ul>

            <h4>Section 8.1 – Dissolution</h4>
            <p>If Learn and Help is dissolved for any reason, 50% of remaining assets shall go to
               <a href="https://nriva.org/pustakamitra" target="_blank" rel="noopener noreferrer" style="color:#4a8500;">NRIVA</a>,
               and 50% to the
               <a href="https://www.alambanafoundation.org/" target="_blank" rel="noopener noreferrer" style="color:#4a8500;">ALAMBAMANA Foundation</a>.
            </p>

            <!-- Article IX -->
            <h3><span class="article-number">Article IX</span> – Amendments</h3>
            <p>These bylaws may be amended or repealed by a majority vote of the Board at any duly constituted meeting, held on Zoom or in any other mutually agreed format.</p>

            <div class="bylaws-note">
                <strong>Note:</strong> This is a draft version of the bylaws and is subject to revision and approval by the Board of Directors.
            </div>

        </div><!-- /content-section -->
    </div><!-- /card -->

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>