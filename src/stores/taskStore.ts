import { ref } from 'vue';

interface Task {
  id: number;
  title: string;
  description: string;
  due_date: string;
  completed: number;
  created_at: string;
  updated_at: string;
}

const currentTask = ref<Task | null>(null);

export const useTaskStore = () => {
  const setCurrentTask = (task: Task | null) => {
    currentTask.value = task;
  };

  const updateTask = (updatedTask: Task) => {
    if (currentTask.value && currentTask.value.id === updatedTask.id) {
      currentTask.value = updatedTask;
    }
  };

  return {
    currentTask,
    setCurrentTask,
    updateTask
  };
}; 