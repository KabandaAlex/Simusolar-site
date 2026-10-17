# Tulima Solar Admin Portal - User Training Guide

**Version:** 2.0  
**Last Updated:** February 22, 2026  
**For:** Administrators, Content Managers, Marketing Team

---

## Table of Contents

1. [Getting Started](#getting-started)
2. [Reports & Analytics](#reports--analytics)
3. [Data Export](#data-export)
4. [Bulk Operations](#bulk-operations)
5. [Activity Log](#activity-log)
6. [Accessibility Features](#accessibility-features)
7. [Tips & Best Practices](#tips--best-practices)
8. [Troubleshooting](#troubleshooting)

---

## Getting Started

### Logging In

1. Navigate to: `yourdomain.com/admin`
2. Enter your username and password
3. Click "Login"
4. You'll be redirected to the Dashboard

**Note:** If you forget your password, contact your system administrator.

### Dashboard Overview

The dashboard provides:
- **Key Metrics**: Page views, unique visitors, contact submissions, products
- **Quick Links**: Fast access to common tasks
- **Recent Activity**: Latest admin actions
- **Notifications**: Unread contact submissions

---

## Reports & Analytics

### Accessing Reports

1. Click "Reports" in the sidebar navigation
2. Select the report type from the tabs:
   - Overview
   - Traffic
   - Content
   - Contacts
   - Products

### Overview Report

The Overview report provides a quick snapshot of your website performance:

**Features:**
- **Traffic Chart**: Daily page views over time
- **Contact Chart**: Contact submissions trend
- **Top Pages Table**: Most viewed pages with engagement metrics
- **Quick Stats**: 
  - Total page views
  - Unique visitors
  - Total contact submissions
  - Average response time

**Use Cases:**
- Daily performance check
- Quick status update
- Identifying popular content
- Monitoring response times

### Traffic Report

Detailed traffic analysis with device and timing insights:

**Features:**
- **Traffic Trends Chart**: Page views and unique visitors comparison
- **Device Breakdown**: Desktop vs. Mobile vs. Tablet
- **Peak Hours**: Best times for contact submissions

**Use Cases:**
- Understanding visitor devices
- Optimizing content for mobile users
- Scheduling content for peak times
- Marketing campaign analysis

**Tips:**
- Use the 7/30/90 day buttons to compare time periods
- High mobile traffic? Ensure pages are mobile-optimized
- Peak hours data helps schedule social media posts

### Content Report

Analyze which pages and products perform best:

**Features:**
- **Page Performance Table**:
  - Page title and slug
  - Total views
  - Unique visitors
  - Average time on page
  - Performance rating (High/Medium/Low)

**Use Cases:**
- Identify high-performing content
- Find pages needing improvement
- Optimize SEO for low-traffic pages
- Content planning based on performance

**Tips:**
- High views + low time on page? Content may need improvement
- Low performance rating? Consider updating or promoting content
- Compare product performance to prioritize marketing efforts

### Contacts Report

Track contact form submissions and response metrics:

**Features:**
- **Submissions Over Time Chart**: Contact trend analysis
- **Status Breakdown**: Read vs. Unread vs. Replied
- **Response Metrics**:
  - Response rate (percentage)
  - Average response time (hours)

**Use Cases:**
- Monitor inquiry volume
- Track response performance
- Identify patterns in submissions
- Improve customer service

**Tips:**
- Aim for <24 hour response time
- High submission volume? Consider auto-responder
- Review response rate to ensure follow-up

### Products Report

Measure product interest and conversion:

**Features:**
- **Product Performance Table**:
  - Product name
  - Category
  - Total views
  - Total inquiries
  - Conversion rate (%)

**Use Cases:**
- Identify popular products
- Track inquiry-to-sale conversion
- Prioritize product marketing
- Optimize product listings

**Tips:**
- High views + low conversion? Improve product description or images
- Track which categories generate most interest
- Use conversion data to inform sales strategies

### Date Range Controls

All reports support date range filtering:

**Quick Filters:**
- Last 7 Days
- Last 30 Days
- Last 90 Days

**Custom Dates:** (Not yet implemented - coming soon)

---

## Data Export

### Exporting Data

Data can be exported to CSV format for analysis in Excel, Google Sheets, or other tools.

### Available Exports

#### 1. Contact Submissions

**Access:** Contact Submissions page → "Export CSV" button

**Includes:**
- Contact name, email, phone
- Subject and message
- Status (read/unread)
- Submission timestamp
- Reply timestamp

**Use Cases:**
- Email marketing lists (with consent)
- Customer relationship management (CRM) import
- Backup and archiving
- Analysis in Excel/Google Sheets

**Best Practices:**
- Regular backups (weekly/monthly)
- Export before major changes
- Keep archived exports for reference

#### 2. Page Views

**Access:** Reports page → Export button

**Includes:**
- Page slug and title
- Visitor information (anonymized)
- Referrer URL
- Time on page
- View timestamp

**Parameters:**
- `days`: Number of days to export (1-365)

**Example URL:**
```
api/export.php?type=page_views&days=30&format=csv
```

**Use Cases:**
- Traffic analysis
- Referrer tracking
- Content performance deep-dive
- User behavior analysis

#### 3. Activity Log

**Access:** Activity Log page → "Export CSV" button

**Includes:**
- Action performed
- Entity type and ID
- Admin user who performed action
- IP address
- Timestamp

**Parameters:**
- `days`: Number of days to export (1-365)

**Use Cases:**
- Audit trail
- Security review
- Change history
- Compliance reporting

#### 4. Products

**Access:** Products page → "Export CSV" button

**Includes:**
- Product title, slug
- Description
- Category and price
- Active status
- Sort order
- Created/updated timestamps

**Use Cases:**
- Product catalog backup
- Price sheet generation
- Inventory management
- Marketing material creation

#### 5. Testimonials

**Access:** Testimonials page → "Export CSV" button

**Includes:**
- Customer name and company
- Role and testimonial text
- Image path
- Active status
- Sort order

**Use Cases:**
- Testimonial catalog
- Marketing material compilation
- Social proof management
- Customer success tracking

#### 6. Partners

**Access:** Partners page → "Export CSV" button

**Includes:**
- Partner name
- Website URL
- Logo path
- Active status
- Sort order

**Use Cases:**
- Partner directory
- Marketing collaterals
- Partnership agreements
- Network documentation

#### 7. Pages

**Access:** Pages page → "Export CSV" button

**Includes:**
- Page slug and title
- Meta description
- Active status
- Hero section settings (title, subtitle, CTAs)
- Video path
- Sort order
- Created/updated timestamps

**Use Cases:**
- Content inventory
- SEO audit
- Site structure documentation
- Content planning

### Export Best Practices

1. **Regular Exports**
   - Weekly backups of critical data
   - Monthly full exports
   - Before major changes

2. **Data Management**
   - Organize exports in folders by date
   - Keep last 6-12 months of exports
   - Delete older exports securely

3. **CSV Handling**
   - Open with Excel or Google Sheets
   - Use UTF-8 encoding for special characters
   - Check for correct date/time formatting

4. **Security**
   - Store exports securely
   - Don't share sensitive data
   - Delete exports after use

### Working with CSV Files

**Opening in Excel:**
1. Open Excel (blank workbook)
2. Data → Get Data → From Text/CSV
3. Select your CSV file
4. Ensure encoding is set to UTF-8
5. Load the data

**Opening in Google Sheets:**
1. Go to sheets.google.com
2. File → Import
3. Upload your CSV file
4. Select "Replace spreadsheet" or "Insert new sheet"

**Common Issues:**
- **Garbled text**: Ensure UTF-8 encoding
- **Missing columns**: Check for hidden rows/columns
- **Date format**: May appear as numbers in Excel

---

## Bulk Operations

### Contact Submissions Bulk Actions

Bulk operations allow you to manage multiple submissions at once.

#### Selecting Submissions

**Select All:**
- Click the checkbox in the table header
- All visible submissions will be selected

**Select Individual:**
- Click checkboxes next to each submission
- Selected submissions are highlighted

**Clear Selection:**
- Click "Clear Selection" button in the toolbar
- Or uncheck all checkboxes manually

#### Bulk Actions

Once submissions are selected, the bulk action toolbar appears:

**1. Mark as Read**
- Marks selected submissions as read
- Removes from unread count
- Updates notification badge

**Use Case:** After reviewing new submissions

**2. Mark as Unread**
- Marks selected submissions as unread
- Adds to unread count
- Useful for follow-up

**Use Case:** Flag submissions requiring attention

**3. Delete Selected**
- Permanently deletes selected submissions
- Requires confirmation
- Action cannot be undone

**Use Case:** Removing spam or test submissions

**Warning:** Deletion is permanent! Always verify before deleting.

#### Bulk Action Best Practices

1. **Before Bulk Actions:**
   - Review selected items carefully
   - Verify the correct action is selected
   - Consider exporting data before deletion

2. **During Bulk Actions:**
   - Wait for completion before navigating away
   - Check for confirmation messages
   - Verify results after action completes

3. **After Bulk Actions:**
   - Review affected items
   - Check notification counts updated
   - Export backup if needed

### Bulk Operations Limitations

**Current Limitations:**
- Bulk operations only available for Contact Submissions
- Maximum 100 items per bulk action
- No undo functionality

**Planned Enhancements:**
- Bulk edit with inline editing
- Bulk copy/duplicate
- Bulk publish/unpublish
- Undo functionality

---

## Activity Log

### Accessing Activity Log

1. Click "Activity Log" in sidebar (Settings section)
2. View all admin actions chronologically

### Understanding Activity Log

Each log entry shows:
- **Timestamp**: When the action occurred
- **Admin User**: Who performed the action
- **Action Type**: What was done (create, update, delete, etc.)
- **Entity Type**: What was affected (page, product, etc.)
- **Entity ID**: ID of the affected item
- **IP Address**: Where the action originated

### Filtering Activity Log

The activity log supports multiple filters:

#### 1. Action Type Filter

**Options:**
- All Actions (default)
- Create (new items added)
- Update (existing items modified)
- Delete (items removed)
- View (items viewed)
- Login (user logged in)
- Logout (user logged out)
- Export (data exported)
- Import (data imported)

**Use Cases:**
- Find when a specific change was made
- Track who created/deleted items
- Monitor login activity
- Review export history

#### 2. Entity Type Filter

**Options:**
- All Entities (default)
- Page
- Product
- Section
- Testimonial
- Partner
- Office
- Contact
- User
- Setting
- Media
- Submission

**Use Cases:**
- Track changes to specific content types
- Monitor product updates
- Review page modifications
- Audit specific entity changes

#### 3. User Filter

**Options:**
- All Users (default)
- Specific admin user

**Use Cases:**
- Track individual admin activity
- Review specific user's actions
- Audit trail for compliance

#### 4. Date Range Filter

**Options:**
- From Date
- To Date
- Quick presets: Last 7 days, Last 30 days, Last 90 days

**Use Cases:**
- Review recent changes
- Monthly activity reports
- Troubleshooting issues
- Time-based audits

### Combining Filters

Multiple filters can be combined for precise searching:

**Example Scenarios:**

1. "All page updates by Admin A in the last 30 days"
   - Action: Update
   - Entity: Page
   - User: Admin A
   - Date: Last 30 days

2. "All deletions in the past week"
   - Action: Delete
   - Entity: All
   - User: All
   - Date: Last 7 days

3. "Product changes by any user this month"
   - Action: Create, Update, Delete
   - Entity: Product
   - User: All
   - Date: Last 30 days

### Exporting Activity Log

Filtered or unfiltered activity logs can be exported:

1. Apply desired filters
2. Click "Export CSV" button
3. Download includes filtered results

**Use Cases:**
- Compliance reporting
- Security audits
- Activity documentation
- External review

### Activity Log Best Practices

1. **Regular Review**
   - Check activity log weekly
   - Look for suspicious activity
   - Verify expected actions

2. **Security Monitoring**
   - Monitor login/logout patterns
   - Check for unexpected deletions
   - Review export activity

3. **Change Tracking**
   - Use filters to find specific changes
   - Document reasons for major changes
   - Keep audit trail for compliance

---

## Accessibility Features

### Skip to Content Link

**What it is:** A keyboard shortcut to jump directly to main content, skipping navigation.

**How to use:**
1. Press `Tab` key once when page loads
2. Press `Enter` on "Skip to main content" link
3. Focus moves directly to main content

**Who it helps:**
- Keyboard-only users
- Screen reader users
- Users with motor disabilities

### Keyboard Navigation

**Tab Navigation:**
- Press `Tab` to move forward through interactive elements
- Press `Shift + Tab` to move backward
- Focus indicator (purple outline) shows current element

**Common Keyboard Shortcuts:**
- `Enter` / `Space`: Activate button or link
- `Escape`: Close modals or dropdowns
- `Tab`: Move to next focusable element
- `Shift + Tab`: Move to previous focusable element

### Focus Indicators

All interactive elements show a clear purple outline when focused:

- Buttons
- Links
- Form inputs (text, email, etc.)
- Textareas
- Select dropdowns
- Checkboxes and radio buttons

**Why it matters:**
- Essential for keyboard navigation
- Helps users track their position
- Required by accessibility standards (WCAG)

### ARIA Labels

Icon-only buttons include descriptive labels:

**Examples:**
- Sidebar toggle: "Toggle sidebar"
- Notifications: "Notifications (3 unread)"
- Dark mode: "Toggle dark mode"
- User menu: "User menu"

**Screen Reader Compatibility:**
- All icons have text descriptions
- Status updates are announced
- Navigation is properly labeled

### Accessibility Best Practices

1. **For Keyboard Users:**
   - Use `Tab` to navigate
   - Watch for focus indicators
   - Use `Escape` to close menus
   - Don't rely on mouse hover

2. **For Screen Reader Users:**
   - Listen for page structure announcements
   - Use headings (H1-H6) to navigate
   - Check for ARIA labels on icons
   - Verify form error messages are announced

3. **For All Users:**
   - Use high contrast mode if needed
   - Adjust text size via browser zoom
   - Use skip link for faster navigation
   - Report accessibility issues

---

## Tips & Best Practices

### Dashboard Management

**Daily Routine (5 minutes):**
1. Check dashboard metrics
2. Review notification count
3. Click through to new submissions
4. Check for any alerts or warnings

**Weekly Routine (30 minutes):**
1. Review all reports
2. Export critical data (contacts, products)
3. Check activity log for anomalies
4. Review and update content as needed

**Monthly Routine (1-2 hours):**
1. Deep dive into analytics
2. Export full activity log
3. Review and optimize content
4. Plan content updates
5. Security audit check

### Report Analysis

**Traffic Report:**
- Compare week-over-week and month-over-month
- Identify upward/downward trends
- Correlate with marketing activities
- Adjust content strategy based on findings

**Content Report:**
- Identify top-performing content
- Plan similar content
- Update or improve low-performing pages
- Check SEO optimization for underperformers

**Contacts Report:**
- Monitor response times
- Identify bottlenecks
- Track submission volume trends
- Adjust staffing if needed

**Products Report:**
- Identify top products for marketing
- Improve low-conversion products
- Update pricing or descriptions
- Track seasonal trends

### Data Management

**Backup Strategy:**
- Weekly: Export contacts and activity log
- Monthly: Full export of all data
- Before major changes: Export affected data
- Store backups securely (3-2-1 rule)

**Data Cleanup:**
- Archive old submissions (after 1 year)
- Remove test data regularly
- Clean up unused media files
- Optimize database (if permitted)

### Security Best Practices

**Daily:**
- Review activity log for suspicious actions
- Check login patterns
- Verify no unexpected exports

**Weekly:**
- Review user access and permissions
- Check for unusual deletions
- Verify data integrity

**Monthly:**
- Full security audit
- Review all admin users
- Check for unauthorized access attempts
- Update documentation

### Content Management

**Creating Content:**
- Always write compelling titles
- Include meta descriptions for SEO
- Use hero sections effectively
- Add relevant CTAs

**Updating Content:**
- Review performance before updates
- Track changes in activity log
- Test after major changes
- Monitor impact on metrics

**Deleting Content:**
- Always export before deletion
- Consider archiving instead
- Check for dependencies
- Verify impact on other pages

---

## Troubleshooting

### Common Issues

#### 1. Reports Not Loading

**Symptoms:**
- Charts show "Loading..."
- No data displays
- Error messages appear

**Solutions:**
- Refresh the page
- Check internet connection
- Clear browser cache
- Try different browser
- Contact system administrator

#### 2. Export Files Not Downloading

**Symptoms:**
- Click export, nothing happens
- File download incomplete
- File won't open

**Solutions:**
- Check browser popup blocker
- Verify download folder
- Try right-click → "Save link as"
- Ensure sufficient disk space
- Try different browser

#### 3. CSV Files Open Incorrectly

**Symptoms:**
- Text appears garbled
- Columns not aligned
- Dates show as numbers

**Solutions:**
- Use UTF-8 encoding when opening
- Import via "Get Data" in Excel
- Use Google Sheets for easier viewing
- Check for hidden characters

#### 4. Bulk Actions Not Working

**Symptoms:**
- Nothing selected when checkboxes checked
- Action buttons unresponsive
- Error after clicking action

**Solutions:**
- Refresh the page
- Check if items are actually selected
- Verify you have permission for the action
- Try selecting fewer items (max 100)
- Check browser console for errors

#### 5. Activity Log Filters Not Working

**Symptoms:**
- Filters don't reduce results
- Date range ignored
- Results disappear entirely

**Solutions:**
- Clear all filters and reapply
- Check date format (YYYY-MM-DD)
- Verify filter selections
- Refresh page
- Contact system administrator if issue persists

#### 6. Keyboard Navigation Issues

**Symptoms:**
- Can't tab through page
- Focus not visible
- Skip link not working

**Solutions:**
- Ensure JavaScript is enabled
- Update browser to latest version
- Try different browser
- Report accessibility issue to admin

### Getting Help

**Before Contacting Support:**
1. Try the solutions above
2. Check browser console for errors
3. Try reproducing in different browser
4. Note exact steps to reproduce issue
5. Capture screenshots if possible

**Contact Information:**
- System Administrator: [Contact info]
- Technical Support: [Contact info]
- Documentation: [URL]

### Reporting Bugs

When reporting bugs, include:
1. **Browser** (name and version)
2. **Page/Feature** where bug occurred
3. **Steps to reproduce** the issue
4. **Expected behavior** vs. **actual behavior**
5. **Screenshots** or error messages
6. **Impact** on workflow

---

## Appendix

### Glossary

**Analytics**: Measurement and analysis of website data  
**CSV**: Comma-Separated Values file format  
**Entity**: An item type (page, product, etc.)  
**Export**: Downloading data to a file  
**Focus**: Current element selected by keyboard  
**Responsive**: Adapts to different screen sizes  
**WCAG**: Web Content Accessibility Guidelines  
**Unique Visitor**: Distinct individual visiting the site  

### Keyboard Shortcuts Reference

| Action | Shortcut |
|---------|-----------|
| Next element | Tab |
| Previous element | Shift + Tab |
| Activate | Enter / Space |
| Skip navigation | Tab → Enter |
| Close menu | Escape |
| Reload page | F5 / Ctrl+R |

### Report Types Quick Reference

| Report Type | Best For | Key Metrics |
|-------------|-----------|--------------|
| Overview | Daily status check | Views, visitors, submissions |
| Traffic | Understanding visitors | Devices, peak hours |
| Content | Performance analysis | Page views, time on page |
| Contacts | Inquiry tracking | Response time, status |
| Products | Sales insights | Views, conversion rate |

### Export Types Quick Reference

| Export Type | Location | Frequency |
|-------------|----------|------------|
| Contact Submissions | Contact Submissions page | Weekly |
| Page Views | Reports page | Monthly |
| Activity Log | Activity Log page | Weekly/Monthly |
| Products | Products page | Monthly |
| Testimonials | Testimonials page | As needed |
| Partners | Partners page | As needed |
| Pages | Pages page | Monthly |

---

## Conclusion

This training guide covers all major features of Phase 4 and Phase 5 of the Tulima Solar Admin Portal. 

**Key Takeaways:**
- Reports provide powerful insights into website performance
- Export data regularly for backup and analysis
- Bulk operations save time on repetitive tasks
- Activity log helps track changes and security
- Accessibility features enable use for all users

**Next Steps:**
1. Practice using the reports with different date ranges
2. Try exporting different data types
3. Test bulk operations with a few items
4. Review activity log filters
5. Explore accessibility features

**Need More Help?**
- Contact your system administrator
- Review technical documentation
- Check for updates and new features

---

**Document Version:** 2.0  
**Last Updated:** February 22, 2026  
**Next Review:** March 2026