# 🏋️‍♀️ Training Tracker App

A comprehensive Progressive Web App (PWA) for tracking daily training progress, built with PHP, SQLite, HTML, and Tailwind CSS.

![Training Tracker](https://img.shields.io/badge/PWA-Ready-green)
![PHP](https://img.shields.io/badge/PHP-7.4+-blue)
![SQLite](https://img.shields.io/badge/SQLite-3-lightgrey)
![Tailwind CSS](https://img.shields.io/badge/Tailwind-CSS-38B2AC)


## 📱 Progressive Web App Features

- **📱 Installable** on mobile devices (iOS/Android)
- **💻 Installable** on desktop (Windows/Mac/Linux)
- **🔄 Offline functionality** with service worker
- **⚡ Fast loading** with cached resources
- **🎨 Native app experience** with custom icons

## ✨ Features

### 👥 People Management
- ➕ **Add people** with name, age, height, and initial weight
- ⚖️ **Update weight monthly** with automatic date tracking
- 🗑️ **Delete people** with confirmation (removes all data)
- 📊 **View all details** in organized table

### 🏋️‍♀️ Training Management
- 🆕 **Create training programs** (Push-ups, Pull-ups, etc.)
- 🎯 **Set daily targets** for each training type
- 👥 **Assign multiple people** to training programs
- ✏️ **Edit training programs** (modify targets, add/remove people)
- 🗑️ **Delete training programs** with confirmation

### 📅 Daily Progress Tracking
- 📱 **Today's training view** with all active programs
- ➕ **Person-specific progress** (click "Add" next to person's name)
- ➖ **Correction support** (use negative numbers to fix mistakes)
- 🔄 **Automatic carry-forward** of incomplete reps to next day
- 📊 **Real-time progress** with remaining reps calculation

### 📈 History & Analytics
- 📋 **Complete training history** for each person
- ⚖️ **Weight tracking over time** with date records
- 📊 **Progress monitoring** across different training types
- 📱 **Easy person selection** for history viewing

### 🌐 Modern Web Features
- 🔗 **URL persistence** (stays on current section after refresh)
- 📱 **Responsive design** (works on all devices)
- ⚡ **Fast performance** with optimized code
- 🎨 **Clean black & white design** using Tailwind CSS

## 🚀 Installation & Setup

### 📁 **For Shared Hosting (Recommended)**

1. **Download the files**
   ```bash
   git clone https://github.com/ibrahimbchara/training-app.git
   cd training-app
   ```

2. **Upload to your hosting**
   Upload these files to your web hosting:
   - `index.php` (main application)
   - `api.php` (backend API)
   - `app.js` (frontend JavaScript)
   - `manifest.json` (PWA configuration)
   - `sw.js` (service worker)

3. **Set permissions**
   ```bash
   chmod 755 /path/to/your/app/directory
   ```

4. **Access your app**
   Visit `https://yourdomain.com/path-to-app/`

### 💻 **For Local Development**

1. **Start PHP server**
   ```bash
   php -S localhost:8000
   ```

2. **Open browser**
   Navigate to `http://localhost:8000`

### 📱 **PWA Installation**

Once deployed, users can install the app:
- **Mobile**: Browser menu → "Add to Home Screen"
- **Desktop**: Address bar install icon → "Install Training Tracker"

## How to Use

### 1. Add People
- Click "People" in the navigation
- Click "Add Person" button
- Fill in name, age, height, and initial weight
- Click "Add Person" to save

### 2. Create Training Programs
- Click "Trainings" in the navigation
- Click "Create Training" button
- Enter training type (e.g., "Push-ups")
- Set daily target (e.g., 100)
- Select participants from the list
- Click "Create Training" to save

### 3. Daily Tracking
- Click "Daily Tracker" in the navigation
- You'll see all active training programs for today
- Click "Add Progress" on any training
- Select the person and enter completed reps
- The system automatically calculates remaining reps

### 4. Update Weight
- Go to "People" section
- Click "Update Weight" for any person
- Enter new weight (this creates a monthly record)

### 5. View History
- Click "History" in the navigation
- Select a person from the dropdown
- View their training progress and weight changes over time

## Database Structure

The app uses SQLite with the following tables:
- `people` - Store person information
- `weight_tracking` - Monthly weight records
- `training_types` - Different training programs
- `training_participants` - Many-to-many relationship between people and trainings
- `daily_progress` - Daily training completion records

## 🛠️ Technology Stack

- **Frontend**: HTML5, Tailwind CSS, Vanilla JavaScript
- **Backend**: PHP 7.4+
- **Database**: SQLite3 (no setup required)
- **PWA**: Service Worker, Web App Manifest
- **Styling**: Tailwind CSS (CDN)
- **Hosting**: Any shared hosting with PHP support

## 📂 File Structure

```
training-app/
├── index.php          # Main application (HTML + PHP)
├── api.php            # Backend API endpoints
├── app.js             # Frontend JavaScript
├── manifest.json      # PWA configuration
├── sw.js              # Service Worker for offline functionality
├── .gitignore         # Git ignore rules
├── README.md          # This file
└── PWA-GUIDE.md       # PWA installation guide
```

## 🔧 Development

For local development:
```bash
# Start PHP development server
php -S localhost:8000

# Or use any local server (XAMPP, WAMP, etc.)
```

## Features Highlights

- **Responsive Design**: Works on desktop and mobile devices
- **Real-time Updates**: All data updates immediately across the interface
- **Data Persistence**: All data is stored in SQLite database
- **Clean UI**: Black and white theme with Tailwind CSS
- **Modal Forms**: User-friendly popup forms for data entry
- **Progress Tracking**: Visual progress indicators and remaining targets
- **Historical Data**: Complete history of training and weight changes

## API Endpoints

- `GET /api/people` - Get all people
- `POST /api/people` - Add new person
- `PUT /api/people/:id/weight` - Update person's weight
- `GET /api/trainings` - Get all training programs
- `POST /api/trainings` - Create new training program
- `GET /api/daily-trainings/:date` - Get daily training progress
- `POST /api/daily-progress` - Add training progress
- `GET /api/history/person/:personId` - Get person's training history
- `GET /api/weight-history/:personId` - Get person's weight history

## Deployment Options

### ⚠️ **Important: This is a Node.js Application**

This app is built with Node.js and **cannot** be deployed to traditional shared hosting that only supports PHP/HTML. You need hosting that supports Node.js applications.

### ✅ **Recommended Hosting Platforms:**

#### 1. **Heroku** (Free tier available)
```bash
# Install Heroku CLI, then:
heroku create your-training-app
git add .
git commit -m "Initial commit"
git push heroku main
```

#### 2. **Railway** (Easy deployment)
- Connect your GitHub repository
- Railway automatically detects Node.js and deploys

#### 3. **Render** (Free tier available)
- Connect GitHub repository
- Set build command: `npm install`
- Set start command: `npm start`

#### 4. **DigitalOcean App Platform**
- Upload your code
- Select Node.js runtime
- Deploy with one click

#### 5. **Vercel** (Serverless)
- Connect GitHub repository
- Automatic deployment on push

#### 6. **VPS/Cloud Server** (More control)
- Any VPS with Node.js support (DigitalOcean, Linode, AWS EC2)
- Install Node.js and run the application

### 🚫 **Won't Work On:**
- Traditional shared hosting (cPanel, GoDaddy shared hosting, etc.)
- Static hosting (GitHub Pages, Netlify for static sites)
- PHP-only hosting

### 📦 **For Shared Hosting Alternative:**
If you only have access to shared hosting, you would need to:
1. Convert the backend to PHP
2. Use MySQL instead of SQLite
3. Rewrite all the API endpoints in PHP

This would be a significant rewrite. I recommend using one of the Node.js hosting platforms above instead.

## License

MIT License
