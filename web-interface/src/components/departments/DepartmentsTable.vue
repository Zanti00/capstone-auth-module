<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { 
  Loader2,
  Building2,
  Users as UsersIcon,
  Edit2,
  Trash2,
  ChevronLeft,
  ChevronRight,
  MoreVertical
} from 'lucide-vue-next'
import type { Department } from '@/types'

const props = defineProps<{
  loading: boolean
  departments: Department[]
  pagination: any
  perPage: number
}>()

const emit = defineEmits<{
  (e: 'update:perPage', value: number): void
  (e: 'fetch', page?: number): void
  (e: 'open-user-modal', dept: Department): void
  (e: 'open-edit-modal', dept: Department): void
  (e: 'delete', id: number): void
}>()

const localPerPage = computed({
  get: () => props.perPage,
  set: (val) => emit('update:perPage', val)
})

const activeDropdownDeptId = ref<number | null>(null)
const toggleDropdown = (id: number) => {
  activeDropdownDeptId.value = activeDropdownDeptId.value === id ? null : id
}

const handleClickOutside = (e: MouseEvent) => {
  const target = e.target as HTMLElement
  if (!target.closest('.dropdown-trigger') && !target.closest('.dropdown-menu')) {
    activeDropdownDeptId.value = null
  }
}

onMounted(() => {
  window.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  window.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div v-if="loading" class="p-12 flex flex-col items-center justify-center gap-4 text-slate-500">
      <Loader2 class="animate-spin" :size="32" />
      <p class="font-medium">Loading departments...</p>
    </div>

    <div v-else-if="departments.length === 0" class="p-12 text-center">
      <div class="w-16 h-16 bg-slate-50 text-slate-400 rounded-2xl flex items-center justify-center mx-auto mb-4">
        <Building2 :size="32" />
      </div>
      <h3 class="text-lg font-bold text-slate-900">No Departments Found</h3>
      <p class="text-slate-500 mt-1">Get started by creating your first department.</p>
    </div>

    <div v-else class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-slate-50/50 border-b border-slate-100">
            <th class="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Department Name</th>
            <th class="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Description</th>
            <th class="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Assigned Users</th>
            <th class="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
          <tr 
            v-for="dept in departments" 
            :key="dept.id"
            class="hover:bg-slate-50/50 transition-colors group"
          >
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-slate-100 text-slate-600 rounded-lg flex items-center justify-center group-hover:bg-[#252578] group-hover:text-white transition-colors">
                  <Building2 :size="16" />
                </div>
                <span class="font-bold text-slate-900">{{ dept.name }}</span>
              </div>
            </td>
            <td class="px-6 py-4">
              <p class="text-sm text-slate-500 line-clamp-1 max-w-xs">{{ dept.description || 'No description' }}</p>
            </td>
            <td class="px-6 py-4 text-center">
              <button 
                @click="emit('open-user-modal', dept)"
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold transition-all hover:scale-105 active:scale-95"
                :class="(dept.users_count ?? 0) > 0 ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'"
              >
                <UsersIcon :size="12" />
                {{ dept.users_count }} Users
              </button>
            </td>
            <td class="px-6 py-4 text-right relative">
              <div class="flex justify-end items-center relative">
                <!-- 3-Dots Action Button -->
                <button 
                  @click.stop="toggleDropdown(dept.id)"
                  class="dropdown-trigger p-2 hover:bg-slate-100 rounded-lg text-slate-400 hover:text-slate-900 transition-all cursor-pointer"
                  title="Actions"
                >
                  <MoreVertical :size="18" />
                </button>

                <!-- Dropdown Menu -->
                <div 
                  v-if="activeDropdownDeptId === dept.id"
                  class="dropdown-menu absolute right-0 top-full mt-1 z-30 bg-white border border-slate-200 rounded-xl shadow-lg p-1.5 flex flex-col gap-1 min-w-[170px] text-left animate-in fade-in slide-in-from-top-1 duration-150"
                >
                  <button 
                    @click.stop="activeDropdownDeptId = null; emit('open-user-modal', dept)"
                    class="w-full px-3 py-2 hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 rounded-lg transition-all flex items-center gap-2.5 text-xs font-semibold cursor-pointer"
                  >
                    <UsersIcon :size="14" class="text-slate-400" />
                    <span>View Users</span>
                  </button>

                  <button 
                    @click.stop="activeDropdownDeptId = null; emit('open-edit-modal', dept)"
                    class="w-full px-3 py-2 hover:bg-slate-50 text-slate-700 hover:text-slate-900 rounded-lg transition-all flex items-center gap-2.5 text-xs font-semibold cursor-pointer"
                  >
                    <Edit2 :size="14" class="text-slate-400" />
                    <span>Edit Department</span>
                  </button>

                  <div class="h-px bg-slate-100 my-0.5"></div>

                  <button 
                    @click.stop="activeDropdownDeptId = null; emit('delete', dept.id)"
                    class="w-full px-3 py-2 hover:bg-red-50 text-red-600 rounded-lg transition-all flex items-center gap-2.5 text-xs font-semibold cursor-pointer"
                  >
                    <Trash2 :size="14" class="text-red-500" />
                    <span>Delete Department</span>
                  </button>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="pagination && pagination.total > 0" class="px-6 py-4 bg-slate-50/50 border-t border-slate-100 flex items-center justify-between">
      <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">
        Showing <span class="text-slate-900 font-bold">{{ departments.length }}</span> of <span class="text-slate-900 font-bold">{{ pagination.total }}</span> departments
      </p>
      <div class="flex items-center gap-6">
        <div class="flex items-center gap-2">
          <span class="text-xs text-slate-500 font-medium uppercase tracking-wider">Rows per page:</span>
          <select 
            v-model.number="localPerPage"
            class="h-8 px-2 pr-6 rounded-lg bg-white border border-slate-200 text-sm font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#3b82f6]/20 focus:border-[#3b82f6] cursor-pointer shadow-sm transition-all"
          >
            <option :value="10">10</option>
            <option :value="15">15</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </div>
        <div class="flex items-center gap-2" v-if="pagination.last_page > 1">
          <button 
            @click="emit('fetch', pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="p-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-50 transition-all shadow-sm"
          >
            <ChevronLeft :size="16" />
          </button>
          <div class="flex items-center gap-1">
            <button 
              v-for="p in pagination.last_page" 
              :key="p"
              @click="emit('fetch', p)"
              class="w-8 h-8 rounded-lg text-sm font-bold transition-all"
              :class="pagination.current_page === p ? 'bg-slate-900 text-white shadow-md' : 'hover:bg-slate-200 text-slate-600'"
            >
              {{ p }}
            </button>
          </div>
          <button 
            @click="emit('fetch', pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="p-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-50 transition-all shadow-sm"
          >
            <ChevronRight :size="16" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
