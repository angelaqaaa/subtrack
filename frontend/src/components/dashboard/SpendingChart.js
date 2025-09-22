import React from 'react';
import { Card } from 'react-bootstrap';
import { Doughnut } from 'react-chartjs-2';
import {
  Chart as ChartJS,
  ArcElement,
  Tooltip,
  Legend
} from 'chart.js';

// Register Chart.js components
ChartJS.register(ArcElement, Tooltip, Legend);

const SpendingChart = ({ categoryTotals }) => {
  if (!categoryTotals || Object.keys(categoryTotals).length === 0) {
    return null;
  }

  const categories = Object.keys(categoryTotals);
  const amounts = Object.values(categoryTotals);

  // Color palette for the chart
  const colors = [
    '#007bff', // Blue
    '#28a745', // Green
    '#ffc107', // Yellow
    '#dc3545', // Red
    '#6f42c1', // Purple
    '#fd7e14', // Orange
    '#20c997', // Teal
    '#6c757d'  // Gray
  ];

  const chartData = {
    labels: categories,
    datasets: [
      {
        data: amounts,
        backgroundColor: colors.slice(0, categories.length),
        borderColor: colors.slice(0, categories.length),
        borderWidth: 1
      }
    ]
  };

  const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'bottom',
        labels: {
          padding: 20,
          usePointStyle: true
        }
      },
      tooltip: {
        callbacks: {
          label: function(context) {
            const label = context.label || '';
            const value = context.parsed || 0;
            const total = amounts.reduce((sum, amount) => sum + amount, 0);
            const percentage = ((value / total) * 100).toFixed(1);
            return `${label}: $${value.toFixed(2)} (${percentage}%)`;
          }
        }
      }
    }
  };

  return (
    <Card>
      <Card.Header>
        <h5 className="mb-0">
          <i className="bi bi-pie-chart me-2"></i>
          Spending by Category
        </h5>
      </Card.Header>
      <Card.Body>
        <div style={{ height: '300px' }}>
          <Doughnut data={chartData} options={chartOptions} />
        </div>
        {categories.length === 0 && (
          <p className="text-muted text-center mt-3">
            Add subscriptions to see your spending breakdown
          </p>
        )}
      </Card.Body>
    </Card>
  );
};

export default SpendingChart;