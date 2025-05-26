<template>
  <page-layout title="Monthly Transaction Report" :show-back-button="false">
    <ion-content class="ion-padding">
      <div class="report-container">
        <!-- Filters -->
        <ion-card class="filter-card">
          <ion-card-header>
            <ion-card-title>
              <ion-icon :icon="filterOutline" class="section-icon"></ion-icon>
              {{ report?.period || 'Monthly' }} Report
            </ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <div class="filter-form">
              <ion-item>
                <ion-label position="stacked">Status</ion-label>
                <ion-select v-model="status" placeholder="Select status">
                  <ion-select-option value="all">All</ion-select-option>
                  <ion-select-option value="completed">Completed</ion-select-option>
                  <ion-select-option value="pending">Pending</ion-select-option>
                </ion-select>
              </ion-item>
              <div class="button-group">
                <ion-button expand="block" @click="generateReport" :disabled="isLoading">
                  <ion-icon :icon="documentTextOutline" slot="start"></ion-icon>
                  {{ isLoading ? 'Generating...' : 'Generate Report' }}
                </ion-button>
                <ion-button 
                  v-if="report" 
                  expand="block" 
                  color="success" 
                  @click="downloadReport">
                  <ion-icon :icon="downloadOutline" slot="start"></ion-icon>
                  Download Report
                </ion-button>
              </div>
            </div>
          </ion-card-content>
        </ion-card>

        <!-- Loading State -->
        <div v-if="isLoading" class="loading-container">
          <ion-spinner></ion-spinner>
          <p>Generating report...</p>
        </div>

        <!-- Error State -->
        <ion-card v-if="errorMessage" class="error-card">
          <ion-card-content>
            <ion-icon :icon="alertCircleOutline" color="danger"></ion-icon>
            <p>{{ errorMessage }}</p>
          </ion-card-content>
        </ion-card>

        <!-- Summary Statistics -->
        <ion-card class="summary-card" v-if="report">
          <ion-card-header>
            <ion-card-title>
              <ion-icon :icon="statsChartOutline" class="section-icon"></ion-icon>
              Summary Statistics
            </ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <div class="stats-grid">
              <div class="stat-item">
                <div class="stat-value">{{ report.summary.total_tasks }}</div>
                <div class="stat-label">Total Tasks</div>
              </div>
              <div class="stat-item">
                <div class="stat-value">{{ report.summary.completed_tasks }}</div>
                <div class="stat-label">Completed</div>
              </div>
              <div class="stat-item">
                <div class="stat-value">{{ report.summary.pending_tasks }}</div>
                <div class="stat-label">Pending</div>
              </div>
              <div class="stat-item">
                <div class="stat-value">{{ report.summary.completion_rate }}%</div>
                <div class="stat-label">Completion Rate</div>
              </div>
            </div>

            <!-- Priority Distribution -->
            <div class="priority-distribution">
              <h4>Priority Distribution</h4>
              <div class="priority-bars">
                <div class="priority-bar">
                  <div class="bar-label">High</div>
                  <div class="bar-container">
                    <div class="bar high" :style="{ width: getPriorityPercentage('high') + '%' }"></div>
                  </div>
                  <div class="bar-value">{{ report.summary.priority_distribution.high }}</div>
                </div>
                <div class="priority-bar">
                  <div class="bar-label">Medium</div>
                  <div class="bar-container">
                    <div class="bar medium" :style="{ width: getPriorityPercentage('medium') + '%' }"></div>
                  </div>
                  <div class="bar-value">{{ report.summary.priority_distribution.medium }}</div>
                </div>
                <div class="priority-bar">
                  <div class="bar-label">Low</div>
                  <div class="bar-container">
                    <div class="bar low" :style="{ width: getPriorityPercentage('low') + '%' }"></div>
                  </div>
                  <div class="bar-value">{{ report.summary.priority_distribution.low }}</div>
                </div>
              </div>
            </div>
          </ion-card-content>
        </ion-card>

        <!-- Tasks List -->
        <ion-card class="tasks-card" v-if="report">
          <ion-card-header>
            <ion-card-title>
              <ion-icon :icon="listOutline" class="section-icon"></ion-icon>
              Tasks Details
            </ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <ion-list>
              <ion-item v-for="task in report.tasks" :key="task.id">
                <ion-label>
                  <h2>{{ task.title }}</h2>
                  <p>{{ task.description }}</p>
                  <div class="task-meta">
                    <ion-badge :color="getStatusColor(task.completed)">
                      {{ task.completed ? 'Completed' : 'Pending' }}
                    </ion-badge>
                    <ion-badge :color="getPriorityColor(task.priority)">
                      {{ task.priority }}
                    </ion-badge>
                    <ion-text color="medium">
                      Due: {{ formatDate(task.due_date) }}
                    </ion-text>
                    <ion-text color="medium">
                      Resources: {{ task.resource_count }}
                    </ion-text>
                    <ion-text color="medium">
                      Owner: {{ task.username }}
                    </ion-text>
                  </div>
                </ion-label>
              </ion-item>
            </ion-list>
          </ion-card-content>
        </ion-card>
      </div>
    </ion-content>
  </page-layout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import api from '@/utils/api';
import {
  IonContent,
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardContent,
  IonItem,
  IonLabel,
  IonSelect,
  IonSelectOption,
  IonDatetime,
  IonButton,
  IonList,
  IonBadge,
  IonText,
  IonFab,
  IonFabButton,
  IonFabList,
  IonIcon,
  toastController,
  IonSpinner
} from '@ionic/vue';
import {
  filterOutline,
  documentTextOutline,
  statsChartOutline,
  listOutline,
  shareOutline,
  documentOutline,
  downloadOutline,
  alertCircleOutline
} from 'ionicons/icons';
import PageLayout from '@/components/PageLayout.vue';

interface Task {
  id: number;
  title: string;
  description: string;
  due_date: string;
  completed: boolean;
  priority: 'low' | 'medium' | 'high';
  resource_count: number;
  username: string;
}

interface PriorityDistribution {
  low: number;
  medium: number;
  high: number;
}

interface ReportSummary {
  total_tasks: number;
  completed_tasks: number;
  pending_tasks: number;
  completion_rate: number;
  total_resources: number;
  priority_distribution: PriorityDistribution;
  tasks_by_date: Record<string, number>;
}

interface Report {
  success: boolean;
  period: string;
  message?: string;
  summary: ReportSummary;
  tasks: Task[];
}

// State
const status = ref('all');
const report = ref<Report | null>(null);
const isLoading = ref(false);
const errorMessage = ref<string | null>(null);

// Get date range for last 14 days
const getDateRange = () => {
  const end = new Date();
  const start = new Date();
  start.setDate(start.getDate() - 14);
  return {
    start_date: start.toISOString().split('T')[0],
    end_date: end.toISOString().split('T')[0]
  };
};

// Generate Report
const generateReport = async () => {
  try {
    isLoading.value = true;
    errorMessage.value = null;
    
    const response = await api.get<Report>('/report.php', {
      params: {
        status: status.value
      }
    });

    if (response.data.success) {
      report.value = response.data;
      const toast = await toastController.create({
        message: 'Report generated successfully',
        duration: 2000,
        color: 'success',
        position: 'top'
      });
      await toast.present();
    } else {
      throw new Error(response.data.message || 'Failed to generate report');
    }
  } catch (err: unknown) {
    const message = err instanceof Error ? err.message : 'Failed to generate report';
    errorMessage.value = message;
    const toast = await toastController.create({
      message,
      duration: 3000,
      color: 'danger',
      position: 'top'
    });
    await toast.present();
  } finally {
    isLoading.value = false;
  }
};

// Generate report automatically on component mount
onMounted(() => {
  generateReport();
});

// Utility Functions
const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getStatusColor = (completed: boolean) => completed ? 'success' : 'warning';

const getPriorityColor = (priority: string) => {
  switch (priority) {
    case 'high': return 'danger';
    case 'medium': return 'warning';
    case 'low': return 'success';
    default: return 'medium';
  }
};

const getPriorityPercentage = (priority: keyof PriorityDistribution): number => {
  if (!report.value) return 0;
  const distribution = report.value.summary.priority_distribution;
  const total = Object.values(distribution).reduce((a, b) => a + b, 0);
  return total > 0 ? (distribution[priority] / total) * 100 : 0;
};

// Export Functions
const generateTextReport = () => {
  if (!report.value) return '';

  const formatSection = (title: string, content: string) => {
    return `=== ${title} ===\n${content}\n\n`;
  };

  const formatDate = (date: string) => {
    return new Date(date).toLocaleString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  let reportContent = 'TASK MANAGEMENT SYSTEM - MONTHLY REPORT\n';
  reportContent += `Period: ${report.value.period}\n`;
  reportContent += `Generated on: ${formatDate(new Date().toISOString())}\n\n`;

  // Overall Statistics
  reportContent += formatSection('OVERALL STATISTICS',
    `Total Tasks: ${report.value.summary.total_tasks}\n` +
    `Completed Tasks: ${report.value.summary.completed_tasks}\n` +
    `Pending Tasks: ${report.value.summary.pending_tasks}\n` +
    `Completion Rate: ${report.value.summary.completion_rate}%\n` +
    `Total Resources: ${report.value.summary.total_resources}`
  );

  // Priority Distribution
  reportContent += formatSection('PRIORITY DISTRIBUTION',
    `High Priority Tasks: ${report.value.summary.priority_distribution.high}\n` +
    `Medium Priority Tasks: ${report.value.summary.priority_distribution.medium}\n` +
    `Low Priority Tasks: ${report.value.summary.priority_distribution.low}`
  );

  // Tasks by Date
  reportContent += formatSection('TASKS BY DATE',
    Object.entries(report.value.summary.tasks_by_date)
      .map(([date, count]) => `${formatDate(date)}: ${count} tasks`)
      .join('\n')
  );

  // Detailed Task List
  reportContent += formatSection('DETAILED TASK LIST',
    report.value.tasks.map(task => (
      `Title: ${task.title}\n` +
      `Description: ${task.description}\n` +
      `Status: ${task.completed ? 'Completed' : 'Pending'}\n` +
      `Priority: ${task.priority}\n` +
      `Due Date: ${formatDate(task.due_date)}\n` +
      `Resources: ${task.resource_count}\n` +
      `Owner: ${task.username}\n` +
      '---'
    )).join('\n\n')
  );

  return reportContent;
};

const downloadReport = () => {
  if (!report.value) return;

  const reportContent = generateTextReport();
  const blob = new Blob([reportContent], { type: 'text/plain' });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `task-report-${new Date().toISOString().split('T')[0]}.txt`;
  document.body.appendChild(a);
  a.click();
  window.URL.revokeObjectURL(url);
  document.body.removeChild(a);
};
</script>

<style scoped>
.report-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 16px;
}

.filter-card,
.summary-card,
.tasks-card {
  margin-bottom: 16px;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.section-icon {
  font-size: 1.4rem;
  margin-right: 8px;
  color: var(--ion-color-primary);
}

.filter-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.date-range {
  display: flex;
  align-items: center;
  gap: 8px;
}

.date-separator {
  margin: 0 8px;
  color: var(--ion-color-medium);
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.stat-item {
  text-align: center;
  padding: 16px;
  background: var(--ion-card-background);
  border-radius: 8px;
  border: 1px solid var(--ion-color-light-shade);
}

.stat-value {
  font-size: 1.8rem;
  font-weight: 600;
  color: var(--ion-color-primary);
  margin-bottom: 4px;
}

.stat-label {
  font-size: 0.9rem;
  color: var(--ion-color-medium);
}

.priority-distribution {
  margin-top: 24px;
}

.priority-distribution h4 {
  margin-bottom: 16px;
  font-size: 1.1rem;
  color: var(--ion-text-color);
}

.priority-bars {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.priority-bar {
  display: flex;
  align-items: center;
  gap: 12px;
}

.bar-label {
  width: 60px;
  font-size: 0.9rem;
  color: var(--ion-color-medium);
}

.bar-container {
  flex: 1;
  height: 8px;
  background: var(--ion-color-light);
  border-radius: 4px;
  overflow: hidden;
}

.bar {
  height: 100%;
  transition: width 0.3s ease;
}

.bar.high { background: var(--ion-color-danger); }
.bar.medium { background: var(--ion-color-warning); }
.bar.low { background: var(--ion-color-success); }

.bar-value {
  width: 40px;
  text-align: right;
  font-size: 0.9rem;
  color: var(--ion-color-medium);
}

.task-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 8px;
}

ion-badge {
  padding: 4px 8px;
  border-radius: 4px;
}

.button-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.task-meta ion-text {
  font-size: 0.9rem;
  margin-right: 12px;
}

@media (max-width: 768px) {
  .report-container {
    padding: 12px;
  }

  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .stat-value {
    font-size: 1.5rem;
  }
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  text-align: center;
}

.loading-container p {
  margin-top: 1rem;
  color: var(--ion-color-medium);
}

.error-card {
  margin: 1rem 0;
  color: var(--ion-color-danger);
  text-align: center;
}

.error-card ion-icon {
  font-size: 2rem;
  margin-bottom: 0.5rem;
}
</style> 