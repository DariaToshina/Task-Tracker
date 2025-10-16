# Task Tracker (PHP)

1 level PHP session-based Task Tracker

### Requirements
- PHP 7.4+
- Any browser

### Installation
1. Clone the repository or download the zip archive.
2. Start the server


### Api endpoints
POST ?action=register_api         Registration
POST ?action=login_api            Login
GET  ?action=me_api&token=...     Me
POST ?action=logout_api&token=... Logout

### General structure of the project
api-task-tracker/
│
├── backend/
│   └── index.php
│
├── frontend/
│   ├── index.html  
│   
│__LIENCE.md
└── README.md