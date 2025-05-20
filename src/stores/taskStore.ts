import { defineStore } from 'pinia';
import axios from 'axios';

interface Task {
  id: number;
  user_id: number;
  title: string;
  description: string;
  due_date: string;
  completed: number;
  created_at: string;
  updated_at: string;
}

export const useTaskStore = defineStore('task', {
  state: () => ({
    tasks: [] as Task[],
    currentTask: null as Task | null,
    loading: false,
    error: null as string | null,
  }),

  getters: {
    getTaskById: (state) => (id: number) => {
      return state.tasks.find(task => task.id === id);
    },
    
    getTasksByStatus: (state) => (completed: boolean) => {
      return state.tasks.filter(task => task.completed === (completed ? 1 : 0));
    },
    
    getTasksByDate: (state) => (date: Date) => {
      const targetDate = new Date(date);
      targetDate.setHours(0, 0, 0, 0);
      
      return state.tasks.filter(task => {
        const taskDate = new Date(task.due_date);
        taskDate.setHours(0, 0, 0, 0);
        return taskDate.getTime() === targetDate.getTime();
      });
    },
  },

  actions: {
    async fetchTasks() {
      this.loading = true;
      this.error = null;
      
      try {
        const token = localStorage.getItem('token');
        if (!token) throw new Error('No authentication token found');

        const response = await axios.get('http://localhost/codes/PROJ/dbConnect/tasks.php', {
          headers: { 'Authorization': `Bearer ${token}` }
        });

        if (response.data.success) {
          this.tasks = response.data.tasks || [];
        } else {
          throw new Error(response.data.message || 'Failed to fetch tasks');
        }
      } catch (error: any) {
        this.error = error.message || 'An error occurred while fetching tasks';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async createTask(taskData: Omit<Task, 'id' | 'user_id' | 'created_at' | 'updated_at'>) {
      this.loading = true;
      this.error = null;
      
      try {
        const token = localStorage.getItem('token');
        if (!token) throw new Error('No authentication token found');

        const response = await axios.post(
          'http://localhost/codes/PROJ/dbConnect/tasks.php',
          taskData,
          {
            headers: { 'Authorization': `Bearer ${token}` }
          }
        );

        if (response.data.success) {
          this.tasks.push(response.data.task);
          return response.data.task;
        } else {
          throw new Error(response.data.message || 'Failed to create task');
        }
      } catch (error: any) {
        this.error = error.message || 'An error occurred while creating task';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateTask(taskId: number, taskData: Partial<Task>) {
      this.loading = true;
      this.error = null;
      
      try {
        const token = localStorage.getItem('token');
        if (!token) throw new Error('No authentication token found');

        const response = await axios.put(
          'http://localhost/codes/PROJ/dbConnect/tasks.php',
          { id: taskId, ...taskData },
          {
            headers: { 'Authorization': `Bearer ${token}` }
          }
        );

        if (response.data.success) {
          const index = this.tasks.findIndex(task => task.id === taskId);
          if (index !== -1) {
            this.tasks[index] = { ...this.tasks[index], ...taskData };
          }
          return response.data.task;
        } else {
          throw new Error(response.data.message || 'Failed to update task');
        }
      } catch (error: any) {
        this.error = error.message || 'An error occurred while updating task';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deleteTask(taskId: number) {
      this.loading = true;
      this.error = null;
      
      try {
        const token = localStorage.getItem('token');
        if (!token) throw new Error('No authentication token found');

        const response = await axios.delete('http://localhost/codes/PROJ/dbConnect/tasks.php', {
          headers: { 'Authorization': `Bearer ${token}` },
          data: { taskId }
        });

        if (response.data.success) {
          this.tasks = this.tasks.filter(task => task.id !== taskId);
          return true;
        } else {
          throw new Error(response.data.message || 'Failed to delete task');
        }
      } catch (error: any) {
        this.error = error.message || 'An error occurred while deleting task';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async toggleTaskCompletion(taskId: number) {
      const task = this.tasks.find(t => t.id === taskId);
      if (!task) throw new Error('Task not found');

      return this.updateTask(taskId, {
        completed: task.completed === 1 ? 0 : 1
      });
    },

    setCurrentTask(task: Task | null) {
      this.currentTask = task;
    },

    clearError() {
      this.error = null;
    },
  },
}); 