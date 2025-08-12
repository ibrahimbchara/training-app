# Training Tracker App - PHP Version

A comprehensive training tracking application built with PHP, SQLite, HTML, and Tailwind CSS. **Perfect for shared hosting!**

## ✅ **Works on ANY Shared Hosting**

This PHP version will work on:
- ✅ cPanel hosting
- ✅ GoDaddy shared hosting  
- ✅ Any hosting with PHP support
- ✅ No special Node.js requirements

## 📁 **Files to Upload**

Upload these files to your hosting:

```
/public_html/training/  (or your domain folder)
├── index.php          (Main application page)
├── api.php            (Backend API handler)
├── app-php.js         (Frontend JavaScript)
├── training_tracker.db (Created automatically)
└── README-PHP.md      (This file)
```

## 🚀 **Installation Steps**

### 1. **Upload Files**
- Upload `index.php`, `api.php`, and `app-php.js` to your hosting
- Make sure they're in the same directory

### 2. **Set Permissions**
- Make sure the directory has write permissions (755 or 777)
- This allows SQLite to create the database file

### 3. **Access Your App**
- Visit: `https://yourdomain.com/training/` (or wherever you uploaded)
- The database will be created automatically on first visit

## 🎯 **Features**

### **People Management**
- Add people with name, age, height, initial weight
- Update weight monthly (automatic date tracking)
- View all person details in a clean table

### **Training Programs**
- Create different training types (Push-ups, Pull-ups, etc.)
- Set daily targets for each training
- Assign multiple people to each training program

### **Daily Tracking**
- View today's training schedule
- Click "Add" next to each person to log their progress
- Automatic calculation of remaining reps
- Cumulative progress (adds to existing reps)

### **History & Analytics**
- View complete training history for each person
- Track weight changes over time
- Historical progress data

## 🔧 **How to Use**

### 1. **Add People**
- Click "People" → "Add Person"
- Enter name, age, height, and initial weight

### 2. **Create Training Programs**
- Click "Trainings" → "Create Training"
- Enter training type (e.g., "Push-ups")
- Set daily target (e.g., 100)
- Select participants

### 3. **Daily Tracking**
- Click "Daily Tracker"
- See all active training programs for today
- Click "Add" next to each person's name
- Enter completed reps (it adds to existing progress)

### 4. **Update Weight**
- Go to "People" section
- Click "Update Weight" for any person
- Enter new weight (creates monthly record)

### 5. **View History**
- Click "History"
- Select a person to see their progress over time

## 🗄️ **Database**

- Uses **SQLite** (no MySQL setup required)
- Database file: `training_tracker.db` (created automatically)
- All data is stored locally on your hosting

## 🎨 **Design**

- **Clean black and white theme** using Tailwind CSS
- **Responsive design** works on mobile and desktop
- **Modal popups** for easy data entry
- **Real-time updates** across all sections

## 🔒 **Security**

- All database queries use prepared statements
- Input validation and sanitization
- CORS headers for API security

## 🆘 **Troubleshooting**

### **Database Permission Error**
- Make sure your hosting directory has write permissions
- Try setting folder permissions to 755 or 777

### **API Not Working**
- Check that `api.php` is in the same directory as `index.php`
- Verify your hosting supports PHP 7.0+

### **Blank Page**
- Check PHP error logs in your hosting control panel
- Make sure all files uploaded correctly

## 📞 **Support**

If you have issues:
1. Check your hosting's PHP error logs
2. Verify file permissions (755 for directories, 644 for files)
3. Make sure SQLite is enabled (most hosting has this by default)

## 🎉 **Advantages of PHP Version**

- ✅ **Works on any shared hosting**
- ✅ **No special server requirements**
- ✅ **Easy to upload and deploy**
- ✅ **SQLite database (no MySQL setup needed)**
- ✅ **Same features as Node.js version**
- ✅ **Better compatibility with traditional hosting**

## 🔄 **Migration from Node.js**

If you were using the Node.js version:
1. Upload these PHP files
2. Your data will be fresh (new database)
3. All features work exactly the same
4. Much easier to deploy and maintain

Enjoy your training tracker! 🏋️‍♀️
