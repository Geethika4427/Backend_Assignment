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

1) **Clone the Repository**<br> 
   git clone https://github.com/Geethika4427/Backend_Assignment.git<br>
   cd user-management

3) **Install Dependencies**<br>
   composer install

4) **Configure Environment Variables**<br>
   > Rename .env.example to .env and set up your database and Twitter API keys:

   DATABASE_URL=mysql: //user:password@127.0.0.1:3306/user_management<br>
   TWITTER_API_KEY= your_api_key<br>
   TWITTER_API_SECRET= your_api_secret<br>
   MAILER_DSN=smtp: //your_smtp_service
  
5) **Run Database Migrations**<br>
   php bin/console doctrine:migrations:migrate
   
6) **Start the Server**<br>
   symfony server:start

## API Endpoints

  **User Management API**
   - POST &nbsp;&nbsp;&nbsp;	  http://127.0.0.1:8000/api/upload/api/upload&nbsp;&nbsp;&nbsp;	   Upload and store user data from CSV
   - GET	 &nbsp;&nbsp;&nbsp;&nbsp;    http://127.0.0.1:8000/api/upload/api/users&nbsp;&nbsp;&nbsp;  	   Retrieve all stored users
   - GET	 &nbsp;&nbsp;&nbsp;    http://127.0.0.1:8000/api/upload/api/backup&nbsp;&nbsp;&nbsp;	   Backup the MySQL database
   - POST &nbsp;&nbsp;&nbsp;    http://127.0.0.1:8000/api/upload/api/restore&nbsp;&nbsp;&nbsp;	   Restore database from backup

   **Twitter OAuth API**
   - GET	 &nbsp;&nbsp;&nbsp;     https://c060-60-243-164-51.ngrok-free.app/auth/twitter/auth/twitter&nbsp;&nbsp;&nbsp;	             Initiate Twitter authentication
   - GET	 &nbsp;&nbsp;&nbsp;     https://c060-60-243-164-51.ngrok-free.app/auth/twitter/callback/auth/twitter/callback&nbsp;&nbsp;&nbsp;	             Handle Twitter OAuth response

 ## Email Notifications
   - Uses Symfony Mailer for sending emails asynchronously
   - Sends email confirmation after CSV upload

 ## Twitter OAuth Configuration
   1. Register a Twitter Developer Account
   2. Create an OAuth 1.0a App in Twitter Developer Portal
   3. Obtain API Key and API Secret Key
   4. Update .env file with these credentials

## Ngrok Setup (For Local Development)
   > If you are testing Twitter OAuth on your local machine, you need Ngrok to expose your local server to the internet. Follow these steps:

   1) Install Ngrok<br>
      If you haven't installed Ngrok yet, download and install it from:
      (Ngrok Official Website)

   2) Start Ngrok<br>
      Run the following command to expose your local Symfony server:<br>
      
      ngrok http 8000

   3) Update Twitter OAuth Callback URL<br>
      Go to Twitter Developer Portal and set your callback URL to:<br>  
      https://random-id.ngrok.io/auth/twitter/callback<br>
      > Replace random-id.ngrok.io with your actual Ngrok URL.

   4) Update .env File<br>
      Modify your .env file to use Ngrok’s URL:<br>
      TWITTER_CALLBACK_URL= https://random-id.ngrok.io/auth/twitter/callback
      
   5)Restart Symfony Server<br>
     Restart your Symfony server to apply the changes:<br>

     symfony server:start

 ## Video Submission

    1. Introduction video link
       [Link Text](https://example.com)
       https://drive.google.com/file/d/1rmrOWn6Jo7t7xtAjMw2nwUQQGzDF-0Gc/view?usp=drive_link
       
    2. Screen Record video
    
       https://drive.google.com/file/d/18e3vg_orHD4gKhgf6ssXBgOdXkT0cW9m/view?usp=drive_link




