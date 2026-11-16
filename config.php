<?php
	$dbServer = (getenv('DB_SERVER') !== false && getenv('DB_SERVER') !== '') ? getenv('DB_SERVER') : (isset($_ENV['DB_SERVER']) && $_ENV['DB_SERVER'] !== '' ? $_ENV['DB_SERVER'] : 'mysql-32d19a6e-abadhbihari-33eb.f.aivencloud.com');
	$dbPort = (getenv('DB_PORT') !== false && getenv('DB_PORT') !== '') ? (int)getenv('DB_PORT') : (isset($_ENV['DB_PORT']) && $_ENV['DB_PORT'] !== '' ? (int)$_ENV['DB_PORT'] : 12893);
	$dbUsername = (getenv('DB_USERNAME') !== false && getenv('DB_USERNAME') !== '') ? getenv('DB_USERNAME') : (isset($_ENV['DB_USERNAME']) && $_ENV['DB_USERNAME'] !== '' ? $_ENV['DB_USERNAME'] : 'avnadmin');
	$dbPassword = (getenv('DB_PASSWORD') !== false && getenv('DB_PASSWORD') !== '') ? getenv('DB_PASSWORD') : (isset($_ENV['DB_PASSWORD']) && $_ENV['DB_PASSWORD'] !== '' ? $_ENV['DB_PASSWORD'] : base64_decode('QVZOU19IX0dqY3JoX1c0WmJURUZsMXVJ'));
	$dbDatabase = (getenv('DB_NAME') !== false && getenv('DB_NAME') !== '') ? getenv('DB_NAME') : (isset($_ENV['DB_NAME']) && $_ENV['DB_NAME'] !== '' ? $_ENV['DB_NAME'] : 'school_attendance');

	$adminConfig = array(
		'adminUsername' => "admin",
		'adminPassword' => "21232f297a57a5a743894a0e4a801fc3",
		'notifyAdminNewMembers' => "",
		'defaultSignUp' => "1",
		'anonymousGroup' => "anonymous",
		'anonymousMember' => "guest",
		'groupsPerPage' => "10",
		'membersPerPage' => "10",
		'recordsPerPage' => "10",
		'custom1' => "Full Name",
		'custom2' => "Address",
		'custom3' => "City",
		'custom4' => "State",
		'MySQLDateFormat' => "%m/%d/%Y",
		'PHPDateFormat' => "n/j/Y",
		'PHPDateTimeFormat' => "m/d/Y, h:i a",
		'senderName' => "Membership management",
		'senderEmail' => "admin@admin.com",
		'approvalSubject' => "Your membership is now approved",
		'approvalMessage' => "Dear member,\n\nYour membership is now approved by the admin. You can log in to your account here:\nhttp://localhost/attendance\n\nRegards,\nAdmin",
		'hide_twitter_feed' => "",
		'maintenance_mode_message' => "<b>Our website is currently down for maintenance</b><br>\r\nWe expect to be back in a couple hours. Thanks for your patience.",
		'mail_function' => "mail",
		'smtp_server' => "",
		'smtp_encryption' => "",
		'smtp_port' => "25",
		'smtp_user' => "",
		'smtp_pass' => ""
	);