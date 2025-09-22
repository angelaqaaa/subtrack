import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Form, Button, Table, Badge, Alert, Spinner } from 'react-bootstrap';
import { subscriptionsAPI, reportsAPI } from '../../services/api';
import { format, subDays, startOfMonth, endOfMonth, startOfYear, endOfYear, parseISO } from 'date-fns';
import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';

const ReportsPage = () => {
  const [subscriptions, setSubscriptions] = useState([]);
  const [reportData, setReportData] = useState({
    totalSpending: 0,
    activeSubscriptions: 0,
    categoryBreakdown: {},
    subscriptionList: []
  });
  const [filters, setFilters] = useState({
    startDate: format(startOfMonth(new Date()), 'yyyy-MM-dd'),
    endDate: format(endOfMonth(new Date()), 'yyyy-MM-dd'),
    category: 'all',
    status: 'all'
  });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [successMessage, setSuccessMessage] = useState('');
  const [exporting, setExporting] = useState(false);

  useEffect(() => {
    loadReportData();
  }, [filters]);

  const loadReportData = async () => {
    try {
      setLoading(true);
      setError('');

      const response = await subscriptionsAPI.getAll();

      if (response.status === 'success') {
        const allSubscriptions = response.data?.subscriptions || [];
        setSubscriptions(allSubscriptions);
        generateReport(allSubscriptions);
      } else {
        setError(response.message || 'Failed to load subscription data');
      }
    } catch (err) {
      console.error('Load report data error:', err);
      setError('Failed to load report data. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const generateReport = (allSubscriptions) => {
    // Filter subscriptions based on criteria
    let filteredSubs = allSubscriptions;

    // Date filter
    if (filters.startDate && filters.endDate) {
      const startDate = new Date(filters.startDate);
      const endDate = new Date(filters.endDate);

      filteredSubs = filteredSubs.filter(sub => {
        const subStartDate = new Date(sub.start_date);
        return subStartDate >= startDate && subStartDate <= endDate;
      });
    }

    // Category filter
    if (filters.category !== 'all') {
      filteredSubs = filteredSubs.filter(sub => sub.category === filters.category);
    }

    // Status filter
    if (filters.status !== 'all') {
      const isActive = filters.status === 'active';
      filteredSubs = filteredSubs.filter(sub => sub.is_active === isActive);
    }

    // Calculate totals
    let totalSpending = 0;
    const categoryBreakdown = {};
    const activeSubscriptions = filteredSubs.filter(sub => sub.is_active).length;

    filteredSubs.forEach(sub => {
      if (sub.is_active) {
        const monthlyCost = sub.billing_cycle === 'monthly'
          ? parseFloat(sub.cost)
          : parseFloat(sub.cost) / 12;

        totalSpending += monthlyCost;

        const category = sub.category || 'Other';
        categoryBreakdown[category] = (categoryBreakdown[category] || 0) + monthlyCost;
      }
    });

    setReportData({
      totalSpending,
      activeSubscriptions,
      categoryBreakdown,
      subscriptionList: filteredSubs
    });
  };

  const handleFilterChange = (e) => {
    setFilters({
      ...filters,
      [e.target.name]: e.target.value
    });
  };

  const setQuickDateRange = (range) => {
    const now = new Date();
    let startDate, endDate;

    switch (range) {
      case 'last30':
        startDate = subDays(now, 30);
        endDate = now;
        break;
      case 'thisMonth':
        startDate = startOfMonth(now);
        endDate = endOfMonth(now);
        break;
      case 'lastMonth':
        const lastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        startDate = startOfMonth(lastMonth);
        endDate = endOfMonth(lastMonth);
        break;
      case 'thisYear':
        startDate = startOfYear(now);
        endDate = endOfYear(now);
        break;
      default:
        return;
    }

    setFilters({
      ...filters,
      startDate: format(startDate, 'yyyy-MM-dd'),
      endDate: format(endDate, 'yyyy-MM-dd')
    });
  };

  const handleExport = async (exportFormat) => {
    try {
      setExporting(true);
      setError(''); // Clear any previous errors

      if (exportFormat === 'csv') {
        const csvContent = generateCSV();
        downloadFile(csvContent, 'subscription-report.csv', 'text/csv');
        setSuccessMessage('CSV report exported successfully!');
      } else if (exportFormat === 'pdf') {
        console.log('Starting PDF export...');
        console.log('Current reportData:', reportData);

        generatePDF();
        setSuccessMessage('PDF report exported successfully!');
      }

      setTimeout(() => setSuccessMessage(''), 3000);
    } catch (err) {
      console.error('Export error details:', {
        message: err.message,
        stack: err.stack,
        reportData: reportData
      });
      setError(`Failed to export report: ${err.message}`);
    } finally {
      setExporting(false);
    }
  };

  const generateCSV = () => {
    const headers = ['Service Name', 'Category', 'Cost', 'Currency', 'Billing Cycle', 'Start Date', 'End Date', 'Status'];
    const rows = [headers.join(',')];

    reportData.subscriptionList.forEach(sub => {
      const row = [
        `"${sub.service_name}"`,
        `"${sub.category || 'Other'}"`,
        sub.cost,
        sub.currency,
        sub.billing_cycle,
        sub.start_date,
        sub.end_date || '',
        sub.is_active ? 'Active' : 'Inactive'
      ];
      rows.push(row.join(','));
    });

    return rows.join('\n');
  };

  const generatePDF = () => {
    try {
      console.log('Starting PDF generation...');
      console.log('Report data:', reportData);

      const doc = new jsPDF();

      // Check if autoTable is available
      if (typeof autoTable !== 'function') {
        throw new Error('jsPDF autoTable plugin not loaded properly');
      }

      // Add title
      doc.setFontSize(20);
      doc.text('SubTrack - Subscription Report', 20, 20);

      // Add date range
      doc.setFontSize(12);
      doc.text(`Report Period: ${filters.startDate} to ${filters.endDate}`, 20, 35);

      // Add summary
      doc.setFontSize(14);
      doc.text('Summary', 20, 50);
      doc.setFontSize(11);
      doc.text(`Active Subscriptions: ${reportData.activeSubscriptions || 0}`, 20, 60);
      doc.text(`Monthly Total: ${formatCurrency(reportData.totalSpending || 0)}`, 20, 70);
      doc.text(`Annual Projection: ${formatCurrency((reportData.totalSpending || 0) * 12)}`, 20, 80);

      // Add category breakdown
      if (reportData.categoryBreakdown && Object.keys(reportData.categoryBreakdown).length > 0) {
        doc.setFontSize(14);
        doc.text('Category Breakdown', 20, 100);

        let yPos = 110;
        Object.entries(reportData.categoryBreakdown).forEach(([category, amount]) => {
          doc.setFontSize(11);
          doc.text(`${category}: ${formatCurrency(amount || 0)}`, 25, yPos);
          yPos += 10;
        });
      }

      // Add subscription table
      const categoryCount = reportData.categoryBreakdown ? Object.keys(reportData.categoryBreakdown).length : 0;
      const tableStartY = Math.max(130, 110 + categoryCount * 10 + 20);

      doc.setFontSize(14);
      doc.text('Subscription Details', 20, tableStartY - 10);

      // Ensure we have subscription data to work with
      const subscriptionList = reportData.subscriptionList || [];
      console.log('Subscription list for PDF:', subscriptionList);

      if (subscriptionList.length === 0) {
        // Create empty table with message
        const tableData = [['No subscriptions found for this period', '', '', '', '', '']];
        console.log('Adding empty table with message');

        autoTable(doc, {
          startY: tableStartY,
          head: [['Service', 'Category', 'Cost', 'Cycle', 'Start Date', 'Status']],
          body: tableData,
          theme: 'striped',
          headStyles: { fillColor: [63, 81, 181] },
          styles: { fontSize: 9 },
        });
      } else {
        const tableData = subscriptionList.map(sub => {
        let formattedDate = 'N/A';
        try {
          if (sub.start_date) {
            // Handle different date formats
            if (sub.start_date.includes('T')) {
              formattedDate = format(parseISO(sub.start_date), 'MMM dd, yyyy');
            } else {
              formattedDate = format(parseISO(sub.start_date + 'T00:00:00'), 'MMM dd, yyyy');
            }
          }
        } catch (err) {
          console.warn('Date parsing error for subscription:', sub.service_name, err);
          formattedDate = sub.start_date || 'N/A';
        }

          return [
            sub.service_name || 'Unknown Service',
            sub.category || 'Other',
            formatCurrency(sub.cost || 0),
            sub.billing_cycle || 'monthly',
            formattedDate,
            sub.is_active ? 'Active' : 'Inactive'
          ];
        });

        console.log('Adding table with data:', tableData);

        autoTable(doc, {
          startY: tableStartY,
          head: [['Service', 'Category', 'Cost', 'Cycle', 'Start Date', 'Status']],
          body: tableData,
          theme: 'striped',
          headStyles: { fillColor: [63, 81, 181] },
          styles: { fontSize: 9 },
          columnStyles: {
            2: { halign: 'right' },
            4: { halign: 'center' },
            5: { halign: 'center' }
          }
        });
      }

      console.log('Saving PDF...');
      // Save the PDF
      doc.save(`subtrack-report-${format(new Date(), 'yyyy-MM-dd')}.pdf`);
      console.log('PDF saved successfully');

    } catch (error) {
      console.error('PDF generation error:', error);
      throw new Error('Failed to generate PDF: ' + error.message);
    }
  };

  const downloadFile = (content, filename, mimeType) => {
    const blob = new Blob([content], { type: mimeType });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
  };

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD'
    }).format(amount);
  };

  const getCategories = () => {
    const categories = [...new Set(subscriptions.map(sub => sub.category))];
    return categories.filter(cat => cat).sort();
  };

  if (loading) {
    return (
      <Container>
        <div className="text-center py-5">
          <div className="spinner-border" role="status">
            <span className="visually-hidden">Loading...</span>
          </div>
          <div className="mt-2">Loading reports...</div>
        </div>
      </Container>
    );
  }

  return (
    <Container>
      <Row>
        <Col>
          <div className="d-flex justify-content-between align-items-center mb-4">
            <h1 className="h2">
              <i className="bi bi-bar-chart me-2"></i>
              Reports
            </h1>
            <div className="btn-group">
              <Button
                variant="outline-success"
                onClick={() => handleExport('csv')}
                disabled={exporting}
              >
                {exporting ? (
                  <Spinner animation="border" size="sm" className="me-2" />
                ) : (
                  <i className="bi bi-download me-2"></i>
                )}
                Export CSV
              </Button>
              <Button
                variant="outline-danger"
                onClick={() => handleExport('pdf')}
                disabled={exporting}
              >
                <i className="bi bi-file-pdf me-2"></i>
                Export PDF
              </Button>
            </div>
          </div>

          {successMessage && (
            <Alert variant="success" dismissible onClose={() => setSuccessMessage('')}>
              <i className="bi bi-check-circle me-2"></i>
              {successMessage}
            </Alert>
          )}

          {error && (
            <Alert variant="danger" dismissible onClose={() => setError('')}>
              <i className="bi bi-exclamation-triangle me-2"></i>
              {error}
            </Alert>
          )}
        </Col>
      </Row>

      {/* Filters */}
      <Row className="mb-4">
        <Col>
          <Card>
            <Card.Header>
              <h5 className="mb-0">
                <i className="bi bi-funnel me-2"></i>
                Filters
              </h5>
            </Card.Header>
            <Card.Body>
              <Row>
                <Col md={3}>
                  <Form.Group className="mb-3">
                    <Form.Label>Start Date</Form.Label>
                    <Form.Control
                      type="date"
                      name="startDate"
                      value={filters.startDate}
                      onChange={handleFilterChange}
                    />
                  </Form.Group>
                </Col>
                <Col md={3}>
                  <Form.Group className="mb-3">
                    <Form.Label>End Date</Form.Label>
                    <Form.Control
                      type="date"
                      name="endDate"
                      value={filters.endDate}
                      onChange={handleFilterChange}
                    />
                  </Form.Group>
                </Col>
                <Col md={3}>
                  <Form.Group className="mb-3">
                    <Form.Label>Category</Form.Label>
                    <Form.Select
                      name="category"
                      value={filters.category}
                      onChange={handleFilterChange}
                    >
                      <option value="all">All Categories</option>
                      {getCategories().map(category => (
                        <option key={category} value={category}>{category}</option>
                      ))}
                    </Form.Select>
                  </Form.Group>
                </Col>
                <Col md={3}>
                  <Form.Group className="mb-3">
                    <Form.Label>Status</Form.Label>
                    <Form.Select
                      name="status"
                      value={filters.status}
                      onChange={handleFilterChange}
                    >
                      <option value="all">All Statuses</option>
                      <option value="active">Active Only</option>
                      <option value="inactive">Inactive Only</option>
                    </Form.Select>
                  </Form.Group>
                </Col>
              </Row>

              <div className="d-flex gap-2 flex-wrap">
                <Button
                  variant="outline-secondary"
                  size="sm"
                  onClick={() => setQuickDateRange('last30')}
                >
                  Last 30 Days
                </Button>
                <Button
                  variant="outline-secondary"
                  size="sm"
                  onClick={() => setQuickDateRange('thisMonth')}
                >
                  This Month
                </Button>
                <Button
                  variant="outline-secondary"
                  size="sm"
                  onClick={() => setQuickDateRange('lastMonth')}
                >
                  Last Month
                </Button>
                <Button
                  variant="outline-secondary"
                  size="sm"
                  onClick={() => setQuickDateRange('thisYear')}
                >
                  This Year
                </Button>
              </div>
            </Card.Body>
          </Card>
        </Col>
      </Row>

      {/* Summary Cards */}
      <Row className="mb-4">
        <Col md={4}>
          <Card className="text-center">
            <Card.Body>
              <h4 className="text-primary">{reportData.activeSubscriptions}</h4>
              <small className="text-muted">Active Subscriptions</small>
            </Card.Body>
          </Card>
        </Col>
        <Col md={4}>
          <Card className="text-center">
            <Card.Body>
              <h4 className="text-success">{formatCurrency(reportData.totalSpending)}</h4>
              <small className="text-muted">Monthly Total</small>
            </Card.Body>
          </Card>
        </Col>
        <Col md={4}>
          <Card className="text-center">
            <Card.Body>
              <h4 className="text-warning">{formatCurrency(reportData.totalSpending * 12)}</h4>
              <small className="text-muted">Annual Projection</small>
            </Card.Body>
          </Card>
        </Col>
      </Row>

      <Row>
        {/* Category Breakdown */}
        <Col md={4}>
          <Card className="mb-4">
            <Card.Header>
              <h5 className="mb-0">
                <i className="bi bi-pie-chart me-2"></i>
                Category Breakdown
              </h5>
            </Card.Header>
            <Card.Body>
              {Object.keys(reportData.categoryBreakdown).length > 0 ? (
                <div>
                  {Object.entries(reportData.categoryBreakdown).map(([category, amount]) => (
                    <div key={category} className="d-flex justify-content-between mb-2">
                      <span>{category}</span>
                      <strong>{formatCurrency(amount)}</strong>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="text-center text-muted">
                  <i className="bi bi-pie-chart" style={{ fontSize: '2rem' }}></i>
                  <p className="mt-2 mb-0">No data for selected period</p>
                </div>
              )}
            </Card.Body>
          </Card>
        </Col>

        {/* Subscription List */}
        <Col md={8}>
          <Card>
            <Card.Header>
              <h5 className="mb-0">
                <i className="bi bi-list-ul me-2"></i>
                Subscriptions ({reportData.subscriptionList.length})
              </h5>
            </Card.Header>
            <Card.Body>
              {reportData.subscriptionList.length > 0 ? (
                <Table responsive hover>
                  <thead>
                    <tr>
                      <th>Service</th>
                      <th>Category</th>
                      <th>Cost</th>
                      <th>Cycle</th>
                      <th>Start Date</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    {reportData.subscriptionList.map((subscription) => (
                      <tr key={subscription.id}>
                        <td><strong>{subscription.service_name}</strong></td>
                        <td>
                          <Badge bg="secondary" pill>
                            {subscription.category || 'Other'}
                          </Badge>
                        </td>
                        <td>{formatCurrency(subscription.cost)}</td>
                        <td>
                          <span className="text-capitalize">{subscription.billing_cycle}</span>
                        </td>
                        <td>
                          <small className="text-muted">
                            {format(parseISO(subscription.start_date + 'T00:00:00'), 'MMM dd, yyyy')}
                          </small>
                        </td>
                        <td>
                          <Badge
                            bg={subscription.is_active ? 'success' : 'danger'}
                            pill
                          >
                            {subscription.is_active ? 'Active' : 'Inactive'}
                          </Badge>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </Table>
              ) : (
                <div className="text-center py-4">
                  <i className="bi bi-inbox text-muted" style={{ fontSize: '3rem' }}></i>
                  <h6 className="text-muted mt-2">No subscriptions found</h6>
                  <p className="text-muted">
                    No subscriptions match your current filter criteria.
                  </p>
                </div>
              )}
            </Card.Body>
          </Card>
        </Col>
      </Row>
    </Container>
  );
};

export default ReportsPage;