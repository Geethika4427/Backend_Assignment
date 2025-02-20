# Backend Assignment – User Data Management & Twitter OAuth API 

## Overview
This project implements a User Data Management API with Twitter OAuth authentication. It allows user data to be uploaded via CSV, stored in MySQL, and authenticated using Twitter.

## Tech Stack
Language: PHP
Framework: Symfony 7
Database: MySQL
OAuth Provider: Twitter
Email Service: Symfony Mailer

## Installation & Setup

1) **Clone the Repository**
   git clone https://github.com/Geethika4427/Backend_Assignment.git
   
   cd user-management

3) **Install Dependencies**
   composer install

4) **Configure Environment Variables**
   > Rename .env.example to .env and set up your database and Twitter API keys:

   DATABASE_URL=mysql://user:password@127.0.0.1:3306/user_management
   
   TWITTER_API_KEY=your_api_key
   
   TWITTER_API_SECRET=your_api_secret
   
   MAILER_DSN=smtp://your_smtp_service
  
5) **Run Database Migrations**
   php bin/console doctrine:migrations:migrate
   
6) **Start the Server**
   symfony server:start

## API Endpoints

  **User Management API**
   - POST	  /api/upload	   Upload and store user data from CSV
   - GET	  /api/users  	 Retrieve all stored users
   - GET	  /api/backup	   Backup the MySQL database
   - POST	  /api/restore	 Restore database from backup

   **Twitter OAuth API**
   - GET	 /auth/twitter	            Initiate Twitter authentication
   - GET	 /auth/twitter/callback	    Handle Twitter OAuth response

 ## Email Notifications
   - Uses Symfony Mailer for sending emails asynchronously
   - Sends email confirmation after CSV upload

 ## Twitter OAuth Configuration
   1. Register a Twitter Developer Account
   2. Create an OAuth 1.0a App in Twitter Developer Portal
         - https://your-domain.com/auth/twitter/callback
   3. Obtain API Key and API Secret Key
   4. Update .env file with these credentials

 ## Video Submission
   ()




