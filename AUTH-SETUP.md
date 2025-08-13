# 🔐 Authentication Setup Guide

## 🎉 Your Training App Now Has Login Protection!

Your app now has two modes:
- **👀 View Mode**: Anyone can view data (no login required)
- **✏️ Edit Mode**: Login required to add, edit, or delete data

## 🔑 Default Login Credentials

**Username:** `admin`  
**Password:** `training123`

⚠️ **IMPORTANT**: Change these credentials in `auth.php` before deploying!

## 📁 Files to Upload

Upload these 6 files to your hosting:
1. **`index.php`** ✅ (updated with login modal)
2. **`api.php`** ✅ (updated with auth checks)
3. **`app.js`** ✅ (updated with auth functions)
4. **`auth.php`** ✅ (new authentication system)
5. **`manifest.json`** ✅ (PWA configuration)
6. **`sw.js`** ✅ (service worker)

## 🔧 How to Change Password

1. **Open `auth.php`** in a text editor
2. **Find these lines:**
   ```php
   $ADMIN_USERNAME = 'admin';
   $ADMIN_PASSWORD = 'training123'; // Change this password!
   ```
3. **Change to your preferred credentials:**
   ```php
   $ADMIN_USERNAME = 'your_username';
   $ADMIN_PASSWORD = 'your_secure_password';
   ```
4. **Save and upload** the file

## 🎯 How It Works

### **For Visitors (No Login):**
- ✅ Can view all people and their data
- ✅ Can view all training programs
- ✅ Can view daily progress
- ✅ Can view history and analytics
- ❌ Cannot add, edit, or delete anything

### **For Admins (With Login):**
- ✅ Everything visitors can do, PLUS:
- ✅ Add new people
- ✅ Update weights
- ✅ Delete people
- ✅ Create training programs
- ✅ Edit training programs
- ✅ Delete training programs
- ✅ Add daily progress
- ✅ Correct mistakes (negative reps)

## 🔒 Security Features

### **Session Management:**
- Sessions expire after 24 hours
- Automatic logout on session expiry
- Secure session handling

### **API Protection:**
- All write operations require authentication
- Read operations remain public
- Clear error messages for unauthorized access

### **User Experience:**
- Login modal appears when auth is needed
- Clear visual indicators (View Only vs Admin)
- Smooth login/logout process

## 📱 User Interface

### **Navigation Bar:**
- **View Mode**: Shows "👀 View Only" and green "Login" button
- **Edit Mode**: Shows "👤 Admin" and red "Logout" button

### **Edit Buttons:**
- **View Mode**: All add/edit/delete buttons are hidden
- **Edit Mode**: All buttons are visible and functional

### **Login Modal:**
- Clean, professional design
- Username and password fields
- Clear instructions about view vs edit modes

## 🚀 Testing the Authentication

1. **Visit your app** without logging in
2. **Try to add a person** → Should show login modal
3. **Login with credentials** → Should enable edit mode
4. **Try adding a person again** → Should work
5. **Logout** → Should return to view-only mode

## 🎉 Benefits

### **For Public Access:**
- People can view progress without login
- Great for family members to check progress
- No accidental data changes

### **For Admins:**
- Secure data management
- Easy login/logout process
- Full control over all data

### **For Hosting:**
- Works on any PHP hosting
- No database changes needed
- Simple session-based auth

## 🔧 Advanced Configuration

### **Change Session Timeout:**
In `auth.php`, modify this line:
```php
$sessionLifetime = 24 * 60 * 60; // 24 hours in seconds
```

### **Add Multiple Users:**
You can extend the login function to support multiple users by creating an array of credentials.

### **Enable HTTPS:**
For production, always use HTTPS to protect login credentials.

## 🎯 Ready to Deploy!

Your training app now has professional authentication:
- 👀 **Public viewing** for transparency
- 🔐 **Secure editing** for data protection
- 📱 **Great user experience** for everyone

Upload the 6 files and enjoy your secure training tracker! 🏋️‍♀️
