<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
$pdo = getDb();
$currentNav = 'reports';
$pageTitle = 'Reports';

// Get report type from query string
$reportType = $_GET['report'] ?? 'overview';

// Get date range from query string
$days = min(max((int)($_GET['days'] ?? 30), 1), 365);
$startDate = date('Y-m-d', strtotime("-$days days"));
$endDate = date('Y-m-d');

require_once __DIR__ . '/../config/path.php';
$basePath = BASE_PATH;
include __DIR__ . '/includes/header.php';
?>

<div class="admin-page-intro">
  <div class="admin-page-intro__content">
    <h1>Reports</h1>
    <p class="admin-page-intro__subtitle">Generate and view comprehensive reports about your website's performance.</p>
  </div>
  <div class="admin-page-intro__actions">
    <a href="reports.php?report=overview&days=7" class="btn btn--secondary btn--sm">Last 7 Days</a>
    <a href="reports.php?report=overview&days=30" class="btn btn--secondary btn--sm">Last 30 Days</a>
    <a href="reports.php?report=overview&days=90" class="btn btn--secondary btn--sm">Last 90 Days</a>
  </div>
</div>

<!-- Report Type Tabs -->
<div class="admin-card" style="margin-bottom: var(--space-6);">
  <div class="admin-card__body" style="padding: var(--space-4);">
    <div style="display: flex; gap: var(--space-2);">
      <a href="reports.php?report=overview&days=<?php echo $days; ?>" 
         class="btn <?php echo $reportType === 'overview' ? 'btn--primary' : 'btn--outline'; ?> btn--sm">
        Overview
      </a>
      <a href="reports.php?report=traffic&days=<?php echo $days; ?>" 
         class="btn <?php echo $reportType === 'traffic' ? 'btn--primary' : 'btn--outline'; ?> btn--sm">
        Traffic
      </a>
      <a href="reports.php?report=content&days=<?php echo $days; ?>" 
         class="btn <?php echo $reportType === 'content' ? 'btn--primary' : 'btn--outline'; ?> btn--sm">
        Content
      </a>
      <a href="reports.php?report=contacts&days=<?php echo $days; ?>" 
         class="btn <?php echo $reportType === 'contacts' ? 'btn--primary' : 'btn--outline'; ?> btn--sm">
        Contacts
      </a>
      <a href="reports.php?report=products&days=<?php echo $days; ?>" 
         class="btn <?php echo $reportType === 'products' ? 'btn--primary' : 'btn--outline'; ?> btn--sm">
        Products
      </a>
    </div>
  </div>
</div>

<?php if ($reportType === 'overview'): ?>
  <!-- Overview Report -->
  <div class="dashboard-grid">
    <div class="admin-card">
      <div class="admin-card__header">
        <span class="admin-card__title">
          <svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
          Traffic Overview
        </span>
        <div class="chart-actions">
          <a href="api/export.php?type=page_views&days=<?php echo $days; ?>&format=csv" 
             class="btn btn--outline btn--sm">
            <svg viewBox="0 0 24 24" style="width: 16px; height: 16px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export
          </a>
        </div>
      </div>
      <div class="admin-card__body">
        <div class="chart-container">
          <canvas id="trafficChart"></canvas>
        </div>
      </div>
    </div>
    
    <div class="admin-card">
      <div class="admin-card__header">
        <span class="admin-card__title">
          <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
          Contact Submissions
        </span>
        <div class="chart-actions">
          <a href="api/export.php?type=contact_submissions&format=csv" 
             class="btn btn--outline btn--sm">
            <svg viewBox="0 0 24 24" style="width: 16px; height: 16px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export
          </a>
        </div>
      </div>
      <div class="admin-card__body">
        <div class="chart-container">
          <canvas id="contactChart"></canvas>
        </div>
      </div>
    </div>
  </div>
  
  <div class="dashboard-grid">
    <div class="admin-card">
      <div class="admin-card__header">
        <span class="admin-card__title">
          <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
          Top Pages
        </span>
      </div>
      <div class="admin-card__body">
        <div class="data-table">
          <table class="data-table__table" id="topPagesTable">
            <thead>
              <tr>
                <th>Page</th>
                <th>Views</th>
                <th>Trend</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colspan="3" class="text-center">Loading...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    
    <div class="admin-card">
      <div class="admin-card__header">
        <span class="admin-card__title">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
          Quick Stats
        </span>
      </div>
      <div class="admin-card__body">
        <div style="display: flex; flex-direction: column; gap: var(--space-4);">
          <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-3); background: var(--gray-50); border-radius: var(--radius-md);">
            <span style="font-weight: 500;">Total Page Views</span>
            <span style="font-weight: 700; font-size: 1.25rem;" id="totalViews">-</span>
          </div>
          <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-3); background: var(--gray-50); border-radius: var(--radius-md);">
            <span style="font-weight: 500;">Unique Visitors</span>
            <span style="font-weight: 700; font-size: 1.25rem;" id="uniqueVisitors">-</span>
          </div>
          <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-3); background: var(--gray-50); border-radius: var(--radius-md);">
            <span style="font-weight: 500;">Contact Submissions</span>
            <span style="font-weight: 700; font-size: 1.25rem;" id="totalContacts">-</span>
          </div>
          <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-3); background: var(--gray-50); border-radius: var(--radius-md);">
            <span style="font-weight: 500;">Avg. Response Time</span>
            <span style="font-weight: 700; font-size: 1.25rem;" id="avgResponse">-</span>
          </div>
        </div>
      </div>
    </div>
  </div>

<?php elseif ($reportType === 'traffic'): ?>
  <!-- Traffic Report -->
  <div class="dashboard-grid">
    <div class="admin-card admin-card--full">
      <div class="admin-card__header">
        <span class="admin-card__title">
          <svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
          Traffic Trends
        </span>
        <div class="chart-actions">
          <a href="api/export.php?type=page_views&days=<?php echo $days; ?>&format=csv" 
             class="btn btn--outline btn--sm">
            <svg viewBox="0 0 24 24" style="width: 16px; height: 16px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export Data
          </a>
        </div>
      </div>
      <div class="admin-card__body">
        <div class="chart-container" style="height: 400px;">
          <canvas id="trafficDetailChart"></canvas>
        </div>
      </div>
    </div>
  </div>
  
  <div class="dashboard-grid">
    <div class="admin-card">
      <div class="admin-card__header">
        <span class="admin-card__title">
          <svg viewBox="0 0 24 24"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
          Device Breakdown
        </span>
      </div>
      <div class="admin-card__body">
        <div class="chart-container">
          <canvas id="deviceChart"></canvas>
        </div>
      </div>
    </div>
    
    <div class="admin-card">
      <div class="admin-card__header">
        <span class="admin-card__title">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          Peak Hours
        </span>
      </div>
      <div class="admin-card__body">
        <div class="chart-container">
          <canvas id="peakHoursChart"></canvas>
        </div>
      </div>
    </div>
  </div>

<?php elseif ($reportType === 'content'): ?>
  <!-- Content Report -->
  <div class="admin-card admin-card--full">
    <div class="admin-card__header">
      <span class="admin-card__title">
        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        Content Performance
      </span>
    </div>
    <div class="admin-card__body">
      <div class="data-table">
        <table class="data-table__table" id="contentTable">
          <thead>
            <tr>
              <th>Page</th>
              <th>Views</th>
              <th>Unique Visitors</th>
              <th>Avg. Time on Page</th>
              <th>Performance</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td colspan="5" class="text-center">Loading...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

<?php elseif ($reportType === 'contacts'): ?>
  <!-- Contacts Report -->
  <div class="dashboard-grid">
    <div class="admin-card admin-card--full">
      <div class="admin-card__header">
        <span class="admin-card__title">
          <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          Contact Submissions Over Time
        </span>
        <div class="chart-actions">
          <a href="api/export.php?type=contact_submissions&format=csv" 
             class="btn btn--outline btn--sm">
            <svg viewBox="0 0 24 24" style="width: 16px; height: 16px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export Data
          </a>
        </div>
      </div>
      <div class="admin-card__body">
        <div class="chart-container" style="height: 400px;">
          <canvas id="contactTrendChart"></canvas>
        </div>
      </div>
    </div>
  </div>
  
  <div class="dashboard-grid">
    <div class="admin-card">
      <div class="admin-card__header">
        <span class="admin-card__title">Submission Status</span>
      </div>
      <div class="admin-card__body">
        <div class="chart-container">
          <canvas id="contactStatusChart"></canvas>
        </div>
      </div>
    </div>
    
    <div class="admin-card">
      <div class="admin-card__header">
        <span class="admin-card__title">Response Metrics</span>
      </div>
      <div class="admin-card__body">
        <div style="display: flex; flex-direction: column; gap: var(--space-4);">
          <div style="text-align: center; padding: var(--space-4);">
            <div style="font-size: 2.5rem; font-weight: 700; color: var(--primary);" id="responseRate">-</div>
            <div style="font-size: 0.875rem; color: var(--text-muted);">Response Rate</div>
          </div>
          <div style="text-align: center; padding: var(--space-4); background: var(--gray-50); border-radius: var(--radius-md);">
            <div style="font-size: 2rem; font-weight: 700; color: var(--text-primary);" id="avgResponseTime">-</div>
            <div style="font-size: 0.875rem; color: var(--text-muted);">Avg. Response Time (hours)</div>
          </div>
        </div>
      </div>
    </div>
  </div>

<?php elseif ($reportType === 'products'): ?>
  <!-- Products Report -->
  <div class="admin-card admin-card--full">
    <div class="admin-card__header">
      <span class="admin-card__title">
        <svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        Product Performance
      </span>
      <div class="chart-actions">
        <a href="api/export.php?type=products&format=csv" 
           class="btn btn--outline btn--sm">
          <svg viewBox="0 0 24 24" style="width: 16px; height: 16px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Export Data
        </a>
      </div>
    </div>
    <div class="admin-card__body">
      <div class="data-table">
        <table class="data-table__table" id="productsTable">
          <thead>
            <tr>
              <th>Product</th>
              <th>Category</th>
              <th>Total Views</th>
              <th>Total Inquiries</th>
              <th>Conversion Rate</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td colspan="5" class="text-center">Loading...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
<script>
// Fetch analytics data
async function fetchAnalytics() {
  try {
    const response = await fetch('api/analytics.php?action=overview');
    const data = await response.json();
    
    // Update quick stats
    document.getElementById('totalViews').textContent = data.totalViews.toLocaleString();
    document.getElementById('uniqueVisitors').textContent = data.uniqueVisitors.toLocaleString();
    document.getElementById('totalContacts').textContent = data.totalSubmissions.toLocaleString();
    
    // Fetch top pages
    const topPagesResponse = await fetch(`api/analytics.php?action=top-pages&limit=5`);
    const topPages = await topPagesResponse.json();
    
    const tbody = document.querySelector('#topPagesTable tbody');
    if (topPages.length > 0) {
      tbody.innerHTML = topPages.map(page => `
        <tr>
          <td>
            <div class="data-table__cell-primary">
              <span class="data-table__cell-title">${page.page_title || page.page_slug}</span>
              <span class="data-table__cell-subtitle">/${page.page_slug}</span>
            </div>
          </td>
          <td class="data-table__metric">${page.views.toLocaleString()}</td>
          <td>
            <span class="badge badge--success">High</span>
          </td>
        </tr>
      `).join('');
    } else {
      tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No data available</td></tr>';
    }
    
    // Initialize charts
    initCharts(data, <?php echo $days; ?>);
    
    // Fetch contact analytics for overview
    const contactResponse = await fetch('api/analytics.php?action=contact-analytics');
    const contactData = await contactResponse.json();
    document.getElementById('avgResponse').textContent = contactData.avgResponseTime?.toFixed(1) + 'h';
    
  } catch (error) {
    console.error('Error fetching analytics:', error);
  }
}

// Initialize charts
function initCharts(data, days) {
  // Traffic chart
  const trafficCtx = document.getElementById('trafficChart');
  if (trafficCtx) {
    fetch(`api/analytics.php?action=traffic-trends&days=${days}`)
      .then(res => res.json())
      .then(trends => {
        new Chart(trafficCtx, {
          type: 'line',
          data: {
            labels: trends.map(t => t.date.slice(5)),
            datasets: [{
              label: 'Page Views',
              data: trends.map(t => t.views),
              borderColor: '#660099',
              backgroundColor: 'rgba(102, 0, 153, 0.1)',
              fill: true,
              tension: 0.4
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { display: false }
            },
            scales: {
              y: { beginAtZero: true }
            }
          }
        });
      });
  }
  
  // Contact chart
  const contactCtx = document.getElementById('contactChart');
  if (contactCtx) {
    fetch('api/analytics.php?action=contact-analytics')
      .then(res => res.json())
      .then(contactData => {
        new Chart(contactCtx, {
          type: 'bar',
          data: {
            labels: contactData.trend.map(t => t.date.slice(5)),
            datasets: [{
              label: 'Submissions',
              data: contactData.trend.map(t => t.count),
              backgroundColor: '#E98E39'
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { display: false }
            },
            scales: {
              y: { beginAtZero: true }
            }
          }
        });
      });
  }
  
  // Traffic detail chart
  const trafficDetailCtx = document.getElementById('trafficDetailChart');
  if (trafficDetailCtx) {
    fetch(`api/analytics.php?action=traffic-trends&days=${days}`)
      .then(res => res.json())
      .then(trends => {
        new Chart(trafficDetailCtx, {
          type: 'line',
          data: {
            labels: trends.map(t => t.date.slice(5)),
            datasets: [
              {
                label: 'Page Views',
                data: trends.map(t => t.views),
                borderColor: '#660099',
                backgroundColor: 'rgba(102, 0, 153, 0.1)',
                fill: true,
                tension: 0.4
              },
              {
                label: 'Unique Visitors',
                data: trends.map(t => t.unique_visitors),
                borderColor: '#E98E39',
                backgroundColor: 'rgba(233, 142, 57, 0.1)',
                fill: true,
                tension: 0.4
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { position: 'bottom' }
            },
            scales: {
              y: { beginAtZero: true }
            }
          }
        });
      });
  }
  
  // Device chart
  const deviceCtx = document.getElementById('deviceChart');
  if (deviceCtx) {
    fetch('api/analytics.php?action=visitor-stats')
      .then(res => res.json())
      .then(visitorData => {
        new Chart(deviceCtx, {
          type: 'doughnut',
          data: {
            labels: visitorData.devices.map(d => d.device),
            datasets: [{
              data: visitorData.devices.map(d => d.count),
              backgroundColor: ['#660099', '#E98E39', '#10B981']
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { position: 'bottom' }
            }
          }
        });
      });
  }
  
  // Peak hours chart
  const peakHoursCtx = document.getElementById('peakHoursChart');
  if (peakHoursCtx) {
    fetch('api/analytics.php?action=contact-analytics')
      .then(res => res.json())
      .then(contactData => {
        new Chart(peakHoursCtx, {
          type: 'bar',
          data: {
            labels: contactData.peakHours.map(h => `${h.hour}:00`),
            datasets: [{
              label: 'Submissions',
              data: contactData.peakHours.map(h => h.count),
              backgroundColor: '#660099'
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { display: false }
            },
            scales: {
              y: { beginAtZero: true }
            }
          }
        });
      });
  }
  
  // Contact trend chart
  const contactTrendCtx = document.getElementById('contactTrendChart');
  if (contactTrendCtx) {
    fetch('api/analytics.php?action=contact-analytics')
      .then(res => res.json())
      .then(contactData => {
        new Chart(contactTrendCtx, {
          type: 'line',
          data: {
            labels: contactData.trend.map(t => t.date.slice(5)),
            datasets: [{
              label: 'Submissions',
              data: contactData.trend.map(t => t.count),
              borderColor: '#660099',
              backgroundColor: 'rgba(102, 0, 153, 0.1)',
              fill: true,
              tension: 0.4
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { display: false }
            },
            scales: {
              y: { beginAtZero: true }
            }
          }
        });
      });
  }
  
  // Contact status chart
  const contactStatusCtx = document.getElementById('contactStatusChart');
  if (contactStatusCtx) {
    fetch('api/analytics.php?action=contact-analytics')
      .then(res => res.json())
      .then(contactData => {
        new Chart(contactStatusCtx, {
          type: 'doughnut',
          data: {
            labels: contactData.byStatus.map(s => s.status),
            datasets: [{
              data: contactData.byStatus.map(s => s.count),
              backgroundColor: ['#10B981', '#F59E0B', '#EF4444', '#94A3B8']
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { position: 'bottom' }
            }
          }
        });
        
        // Update response metrics
        document.getElementById('responseRate').textContent = contactData.responseRate + '%';
        document.getElementById('avgResponseTime').textContent = contactData.avgResponseTime?.toFixed(1) || '-';
      });
  }
}

// Load content performance
function loadContentPerformance() {
  fetch('api/analytics.php?action=content-performance')
    .then(res => res.json())
    .then(data => {
      const tbody = document.querySelector('#contentTable tbody');
      if (data.pages && data.pages.length > 0) {
        tbody.innerHTML = data.pages.map(page => {
          const avgTime = page.avg_time_on_page ? Math.round(page.avg_time_on_page / 60) : 0;
          let performance = 'Medium';
          if (page.views > 100) performance = 'High';
          else if (page.views < 20) performance = 'Low';
          
          return `
            <tr>
              <td>
                <div class="data-table__cell-primary">
                  <span class="data-table__cell-title">${page.page_title || page.page_slug}</span>
                  <span class="data-table__cell-subtitle">/${page.page_slug}</span>
                </div>
              </td>
              <td class="data-table__metric">${page.views.toLocaleString()}</td>
              <td class="data-table__metric">${page.unique_visitors.toLocaleString()}</td>
              <td class="data-table__metric">${avgTime}m</td>
              <td>
                <span class="badge badge--${performance === 'High' ? 'success' : performance === 'Medium' ? 'info' : 'muted'}">${performance}</span>
              </td>
            </tr>
          `;
        }).join('');
      } else {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No data available</td></tr>';
      }
    });
}

// Load products performance
function loadProductsPerformance() {
  fetch('api/analytics.php?action=content-performance')
    .then(res => res.json())
    .then(data => {
      const tbody = document.querySelector('#productsTable tbody');
      if (data.products && data.products.length > 0) {
        tbody.innerHTML = data.products.map(product => {
          const conversion = product.total_views > 0 
            ? ((product.total_inquiries / product.total_views) * 100).toFixed(1) 
            : 0;
          
          return `
            <tr>
              <td>
                <div class="data-table__cell-primary">
                  <span class="data-table__cell-title">${product.title}</span>
                </div>
              </td>
              <td>${product.category || '-'}</td>
              <td class="data-table__metric">${product.total_views.toLocaleString()}</td>
              <td class="data-table__metric">${product.total_inquiries.toLocaleString()}</td>
              <td class="data-table__metric">${conversion}%</td>
            </tr>
          `;
        }).join('');
      } else {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No data available</td></tr>';
      }
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
  const reportType = '<?php echo $reportType; ?>';
  
  if (reportType === 'overview') {
    fetchAnalytics();
  } else if (reportType === 'content') {
    loadContentPerformance();
  } else if (reportType === 'products') {
    loadProductsPerformance();
  } else {
    initCharts(null, <?php echo $days; ?>);
  }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>