🌍 ITCareerSwitch Project — Interactive Country Explorer

A full‑stack Country Explorer web application built as part of my ITCareerSwitch course, using HTML, CSS, JavaScript, PHP, and an external API.
The app allows users to explore countries on an interactive map, toggle map layers, view points of interest, and access detailed country information through dynamic API calls.
✨ Features
🗺️ Interactive Map

    Displays a world map with clickable countries

    Satellite & Street View toggle for switching map styles

    Smooth zoom and pan controls

📍 Points of Interest (POI) Filters

Users can toggle checkboxes to show or hide:

    Airports

    Restaurants

    Museums

POIs appear directly on the map with markers for easy navigation.
🌦️ Weather Modal

Clicking a country opens a modal showing:

    Current weather

    Temperature

    Conditions

    Additional weather details (depending on API data)

📊 Country Statistics & Facts

The modal also includes:

    Population

    Capital city

    Region

    Languages

    Timezones

    Other general facts

💱 Currency Converter

    Live currency conversion using the country’s official currency

    Users can convert between the selected country’s currency and their own

📚 Wikipedia Summary

    Fetches a short Wikipedia extract about the selected country

    Provides a link to the full article for further reading

📰 Country News Headlines

    Displays the latest news headlines related to the selected country

    Pulled dynamically from an external news API

⚙️ Backend Logic (PHP)

    PHP acts as a secure middle layer for API requests

    Helps avoid exposing API keys on the frontend

    Handles server‑side processing where needed

🎨 Clean Frontend UI

    Built with HTML, CSS, and JavaScript

    Responsive layout

    Smooth transitions and modals

    Clear, user‑friendly interface

🧱 Tech Stack
Frontend

    HTML — structure

    CSS — styling and layout

    JavaScript — map logic, API calls, UI updates

Backend

    PHP — server‑side processing

    External APIs — weather, country data, Wikipedia, news, currency

📦 Installation & Setup
1. Clone the repository
bash

git clone https://github.com/your-username/itcareerswitch-project.git

2. Set up a PHP‑enabled environment

Use any of the following:

    XAMPP

    WAMP

    MAMP

    Local PHP server

    Hosting provider with PHP support

Place the project inside your server’s public directory (e.g., htdocs).
3. Add your API keys

Create a config.php file:
php

<?php
$weather_api = "YOUR_API_KEY";
$news_api = "YOUR_API_KEY";
$currency_api = "YOUR_API_KEY";
?>

4. Run the project

Open in your browser:
Code

http://localhost/itcareerswitch-project

🎮 How It Works

    User clicks a country on the map

    JavaScript fetches data via PHP endpoints

    The modal displays:

        Weather

        Country facts

        Currency converter

        Wikipedia summary

        News headlines

    Users can toggle map layers and POI categories

    Map updates dynamically without reloading


# Future Improvements

🚀 Future Improvements

Here are the next steps planned for the project:

    Add loading indicators during API calls

    Improve error messages and validation

    Add more API endpoints or features

    Add a database layer for saving user data

    Add dark mode

    Improve accessibility and keyboard navigation

    Add city search and citry information rather than just by country

    Refine Modal and Toggle details/searches


## 📸 Screenshots

### Homepage
![Homepage](./reposcreenshots/gazeteer%201.png)

### Toggle View
![Toggle View](./reposcreenshots/gazeteer%202.png)

### Weather Modal
![Weather Modal](./reposcreenshots/gazeteer%203.png)

### Country Information
![Country Information](./reposcreenshots/gazeteer%204.png)

### Currency Converter
![Currency Converter](./reposcreenshots/gazeteer%205.png)

### Wikipedia Information
![Wikipedia Information](./reposcreenshots/gazeteer%206.png)

### Country News Headlines
![Country News Headlines](./reposcreenshots/gazeteer%207.png)