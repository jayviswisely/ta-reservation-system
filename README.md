# 📘 Teaching Assistant Consultation Reservation System
To improve course tutoring efficiency and facilitate scheduling consultation times between students and teaching assistants, a full-stack PHP + MySQL web application is established. Built as a university project for the course Database Management to demonstrate database-driven system design and user interaction flow.

---

## 🔧 Features

### 🧑‍🎓 Student Interface

- View personal course list and corresponding TA information
- Check available consultation time slots
- Reserve consultation time slots
- Upload assignments or questions for discussion
- Complete consultation feedback

### 👨‍🏫 TA Interface

- Set personal consultation time slots
- Manage reservation status
- View questions submitted by students
- Record consultation content and time
- Mark frequently asked questions
- View student feedback

### 🧑‍💼 Admin Interface
- Manage course TA information
- Monitor consultation status
- View consultation statistical reports
- Publish relevant announcements

### 🛡️ Authentication
- Secure Login/logout for students, TAs, and admins
- Session-based access control

## 🧱 Tech Stack
- **Frontend**: HTML, CSS, JavaScript
- **Backend**: PHP
- **Database**: MySQL (SQL schema provided)
- **Other**: PHP sessions for authentication and procedures


---

## 📁 Project Structure

```
/
🔍📁 index.php                      # Login page
🔍📁 login.php                     # Login handling
🔍📁 course.php                    # Main course view
🔍📁 ta.php                        # TA dashboard
🔍📁 student.php                   # Student dashboard
🔍📁 admin.php                     # Admin dashboard
🔍📁 delete_announcement.php       # Announcement deletion handling
🔍📁 toggle_reaction.php           # Announcement reaction handling
🔍📁 make_appointment.php          # Appointment booking
🔍📁 manage_schedule.php           # TA schedule management
🔍📁 get_history.php               # Appointment history fetcher
🔍📁 discussion.php                # Course Q&A section
🔍📁 submit_feedback.php           # Handles student feedback
🔍📁 db_connect.php                # Database connection config
🔍📁 ta_reservation_system.sql     # SQL schema
🔍📁 styles.css                    # CSS styles
🔍📁 script.js                     # Frontend JavaScript
🔍📁 phpinfo.php                   # PHP info page (debugging)
🔍📁 logout.png, logo_*.png        # Image assets
🔍📁 LICENSE                       # Project license
🔍📁 README.md                     # Project documentation
```

---

## 🛠️ Setup Instructions

### 1. Clone the repository

```bash
git clone https://github.com/jayviswisely/ta-reservation-system.git
cd ta-reservation-system
```

### 2. Set up the MySQL database

- Create a new MySQL database (e.g., `ta_reservation_system`)
- Import the provided SQL file:

```sql
SOURCE ta_reservation_system.sql;
```

### 3. Configure database connection

Edit `db_connect.php`:

```php
$host = "localhost";
$user = "your_db_user";
$password = "your_db_password";
$dbname = "ta_reservation_system";
```

### 4. Run the project locally

- Use [XAMPP](https://www.apachefriends.org/) or any LAMP/WAMP stack
- Place the project folder inside the `htdocs/` directory
- Access the system via browser:\
  `http://localhost/ta-reservation-system/index.php`

---

## 🚀 Future Improvements

- NCKU Moodle integration for account sync
- Enhanced role-based access control
- Responsive mobile design
- Email notifications for upcoming consultations
- Admin analytics dashboard

---

## 👨‍💻 Authors

**Jayvis Wisely 黃健維**
[GitHub](https://github.com/jayviswisely)\
**Gilbert Karlsen Lyon 梁家銓**\
**Louis Shevchenko 黎益福**\
**Marcus Lee Zhan Sheng 李展陞**

---

## 📄 License

This project is licensed under the [MIT License](./LICENSE).  
Feel free to use, modify, and distribute with proper attribution.