📘 Teaching Assistant Consultation Reservation System
To improve course tutoring efficiency and facilitate scheduling consultation times between students and teaching assistants, a full-stack PHP + MySQL web application is established. Built as a university project for the course Database Management to demonstrate database-driven system design and user interaction flow.

🔧 Features
- 🧑‍🎓 Student Interface
    • View personal course list and corresponding TA information
    • Check available consultation time slots
    • Reserve consultation time slots
    • Upload assignments or questions for discussion
    • Complete consultation feedback

- 👨‍🏫 TA Interface
    • Set personal consultation time slots
    • Manage reservation status
    • View questions submitted by students
    • Record consultation content and time
    • Mark frequently asked questions
    • View student feedback

- 🧑‍💼 Admin Interface
    • Manage course TA information
    • Monitor consultation status
    • View consultation statistical reports
    • Publish relevant announcements


- 🛡️ Authentication
    • Login/logout for students, TAs, and admins
    • Session-based access control

🧱 Tech Stack
Frontend: HTML, CSS, JavaScript
Backend: PHP
Database: MySQL (SQL script provided)
Others: Sessions for auth, PHP procedural code

📁 Project Structure
/
├── index.php               # Login page
├── course.php              # Main course page
├── ta.php                  # TA dashboard
├── student.php             # Student dashboard
├── admin.php               # Admin dashboard
├── make_appointment.php    # Appointment booking
├── manage_schedule.php     # TA schedule management
├── get_history.php         # Fetch appointment history
├── discussion.php          # Course QNA section
├── submit_feedback.php     # Handle feedback submissions
├── db_connect.php          # DB connection config
├── ta_reservation_system_vFeedbackUpdate.sql # Database schema
├── styles.css              # Site-wide styling
├── script.js               # Client-side frontend logic
├── phpinfo.php             # For environment debugging
├── logout.png, logo_*.png  # UI assets
├── README.md               # This file
└── ...

🛠️ Setup Instructions
Clone the repo:
git clone https://github.com/jayviswisely/ta-reservation-system.git
cd ta-reservation-system

Set up the database:
Create a MySQL database (in our files, we use the name ta_reservation_system or you can change it)

Import the provided SQL script:
SOURCE ta_reservation_system_vFeedbackUpdate.sql;

Configure db_connect.php:
$host = "localhost";
$user = "your_db_user";
$password = "your_db_password";
$dbname = "ta_system";

Run locally:
Use XAMPP or similar local server
Place the project folder inside the htdocs/ directory
Navigate to http://localhost/ta-reservation-system/index.php

🚀 Future Improvements
NCKU Moodle Integration for accounts
Role-based access enhancement
Mobile responsiveness
Email notifications for appointment reminders
Admin dashboard analytics

👨‍💻 Author
Jayvis Wisely
[GitHub](https://github.com/jayviswisely)

📄 License
This project is for educational purposes and does not currently include a license. Feel free to fork or contribute with attribution.


