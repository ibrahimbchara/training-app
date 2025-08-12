# 🎉 New Features Added!

## ✅ **What's New:**

### 1. **🔄 Correct Mistakes with Negative Reps**
- **Problem**: You accidentally added 20 reps instead of 10
- **Solution**: Enter `-10` in the progress modal to remove 10 reps
- **Example**: If someone has 30 reps and you enter `-20`, they'll have 10 reps

### 2. **🗑️ Delete People**
- **New**: Red "Delete" button next to each person
- **Safety**: Confirmation dialog before deletion
- **Complete**: Removes all their training data and weight history

### 3. **📅 Automatic Daily Rollover**
- **Smart Carry Forward**: Incomplete reps automatically carry to next day
- **Example**: Target 100 push-ups, only did 60 → Next day shows 140 target (100 + 40 carried)
- **Visual**: Orange text shows "carried from yesterday"

## 🎯 **How to Use:**

### **Fix Mistakes:**
1. Go to Daily Tracker
2. Click "Add" next to person's name
3. Enter negative number (e.g., `-20`)
4. Reps will be subtracted from their total

### **Delete People:**
1. Go to People section
2. Click red "Delete" button
3. Confirm deletion
4. All their data is permanently removed

### **Daily Progression:**
1. **Today**: Person does 60/100 push-ups
2. **Tomorrow**: System automatically shows 140 target (100 + 40 remaining)
3. **Visual**: Orange text shows "+40 carried from yesterday"
4. **Complete**: When they finish 140, they're caught up

## 🔧 **Technical Details:**

### **Database Changes:**
- Added `carried_forward` column to track remaining reps
- Automatic migration for existing databases

### **Smart Logic:**
- Prevents negative total reps (minimum 0)
- Automatic calculation of carry-forward amounts
- Daily rollover happens when you visit Daily Tracker

### **Safety Features:**
- Confirmation before deleting people
- Can't go below 0 total reps
- All deletions are permanent (no undo)

## 📱 **User Experience:**

### **Progress Modal Now Shows:**
- Current target (including carried forward)
- Tip about using negative numbers
- Clear instructions

### **Daily Tracker Shows:**
- Original daily target
- Carried forward amount (if any)
- Total target for today
- Remaining reps needed

### **People Table Shows:**
- Update Weight button (green)
- Delete button (red)
- Clear separation of actions

## 🚀 **Ready to Deploy:**

All features work locally and are ready for upload to your hosting:
- `index.php` (updated with new UI)
- `api.php` (updated with new endpoints)
- `app-php.js` (updated with new functions)

Upload these 3 files and you'll have all the new features! 🎉
