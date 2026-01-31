@extends('log-notifier::layouts.app')

@section('title', 'Error Dashboard')

@section('content')
<div x-data="dashboard()" x-init="init()">
    <!-- Data Source Notice -->
    @if(($source ?? 'database') === 'files')
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zm-11-1a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        <strong>File Mode:</strong> Showing errors parsed from log files. Edit and delete actions are not available in this mode.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-100 text-red-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Errors</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $statistics['total_errors'] ?? $statistics['total'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">This Week</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $statistics['this_week'] ?? $statistics['unresolved'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Today</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $statistics['today'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">By Level</p>
                    <div class="flex space-x-1 mt-1">
                        @foreach(($statistics['by_level'] ?? []) as $level => $count)
                            <span class="text-xs px-2 py-1 rounded-full 
                                @if($level === 'emergency' || $level === 'critical') bg-red-100 text-red-700
                                @elseif($level === 'error') bg-yellow-100 text-yellow-700
                                @elseif($level === 'alert') bg-orange-100 text-orange-700
                                @else bg-gray-100 text-gray-700
                                @endif">
                                {{ $count }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow mb-6 p-4">
        <form method="GET" action="{{ route('log-notifier.dashboard') }}" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input 
                    type="text" 
                    name="search" 
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Search errors..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
            </div>
            
            <div class="w-40">
                <label class="block text-sm font-medium text-gray-700 mb-1">Level</label>
                <select 
                    name="level"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="">All Levels</option>
                    @foreach($levels as $level)
                        <option value="{{ $level }}" {{ ($filters['level'] ?? '') === $level ? 'selected' : '' }}>
                            {{ ucfirst($level) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="w-40">
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select 
                    name="resolved"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="">All Status</option>
                    <option value="0" {{ isset($filters['is_resolved']) && $filters['is_resolved'] === false ? 'selected' : '' }}>Unresolved</option>
                    <option value="1" {{ isset($filters['is_resolved']) && $filters['is_resolved'] === true ? 'selected' : '' }}>Resolved</option>
                </select>
            </div>

            <div>
                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                    Filter
                </button>
                <a href="{{ route('log-notifier.dashboard') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors ml-2">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Bulk Actions -->
    <div x-show="selectedErrors.length > 0" x-cloak class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
        <div class="flex items-center justify-between">
            <span class="text-blue-700">
                <span x-text="selectedErrors.length"></span> error(s) selected
            </span>
            <div class="space-x-2">
                <button @click="bulkAction('resolve')" class="px-3 py-1 bg-green-500 text-white text-sm rounded hover:bg-green-600">
                    Resolve Selected
                </button>
                <button @click="bulkAction('delete')" class="px-3 py-1 bg-red-500 text-white text-sm rounded hover:bg-red-600">
                    Delete Selected
                </button>
                <button @click="selectedErrors = []" class="px-3 py-1 bg-gray-300 text-gray-700 text-sm rounded hover:bg-gray-400">
                    Clear Selection
                </button>
            </div>
        </div>
    </div>

    <!-- Error List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        @if(($source ?? 'database') === 'database')
                            <th class="px-4 py-3 text-left">
                                <input 
                                    type="checkbox" 
                                    @change="toggleSelectAll($event)"
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                >
                            </th>
                        @endif
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Level</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                        @if(($source ?? 'database') === 'database')
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Count</th>
                        @endif
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Occurred</th>
                        @if(($source ?? 'database') === 'database')
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($errors as $error)
                        @php
                            $isModel = is_object($error) && method_exists($error, 'getAttribute');
                            $errorId = $isModel ? $error->id : ($error['id'] ?? uniqid());
                            $level = $isModel ? $error->level : ($error['level'] ?? 'error');
                            $message = $isModel ? $error->excerpt : substr($error['message'] ?? '', 0, 100);
                            $location = $isModel ? $error->location : (($error['file'] ?? 'unknown') . ':' . ($error['line'] ?? '0'));
                            $occurredAt = $isModel ? $error->last_occurred_at->diffForHumans() : \Carbon\Carbon::parse($error['occurred_at'] ?? now())->diffForHumans();
                            $occurrenceCount = $isModel ? $error->occurrence_count : 1;
                            $isResolved = $isModel ? $error->is_resolved : false;
                        @endphp
                        <tr class="hover:bg-gray-50">
                            @if(($source ?? 'database') === 'database')
                                <td class="px-4 py-4">
                                    <input 
                                        type="checkbox" 
                                        value="{{ $errorId }}"
                                        x-model="selectedErrors"
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    >
                                </td>
                            @endif
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($level === 'emergency' || $level === 'critical') bg-red-100 text-red-800
                                    @elseif($level === 'error') bg-yellow-100 text-yellow-800
                                    @elseif($level === 'alert') bg-orange-100 text-orange-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ strtoupper($level) }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                @if(($source ?? 'database') === 'database')
                                    <a href="{{ route('log-notifier.errors.show', $errorId) }}" class="text-gray-900 hover:text-blue-600">
                                        <div class="max-w-md truncate">{{ $message }}</div>
                                    </a>
                                @else
                                    <div class="max-w-md truncate">{{ $message }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                                {{ Str::limit($location, 40) }}
                            </td>
                            @if(($source ?? 'database') === 'database')
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $occurrenceCount }}
                                </td>
                            @endif
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $occurredAt }}
                            </td>
                            @if(($source ?? 'database') === 'database')
                                <td class="px-4 py-4 whitespace-nowrap">
                                    @if($isResolved)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Resolved
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Open
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('log-notifier.errors.show', $errorId) }}" class="text-blue-600 hover:text-blue-800">
                                            View
                                        </a>
                                        @if(!$isResolved)
                                            <form action="{{ route('log-notifier.errors.resolve', $errorId) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-green-600 hover:text-green-800">Resolve</button>
                                            </form>
                                        @else
                                            <form action="{{ route('log-notifier.errors.unresolve', $errorId) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-yellow-600 hover:text-yellow-800">Reopen</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <p class="text-lg font-medium">No errors found</p>
                                    <p class="text-sm">Great job! Your application is running smoothly.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if(is_object($errors) && method_exists($errors, 'hasPages') && $errors->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $errors->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    function dashboard() {
        return {
            selectedErrors: [],
            
            init() {
                // Initialize dashboard
            },

            toggleSelectAll(event) {
                if (event.target.checked) {
                    this.selectedErrors = @json(collect($errors)->map(function($error) {
                        if (is_array($error)) {
                            return $error['id'] ?? null;
                        } else {
                            return $error->id ?? null;
                        }
                    })->filter()->values()->all());
                } else {
                    this.selectedErrors = [];
                }
            },

            async bulkAction(action) {
                if (!confirm(`Are you sure you want to ${action} the selected errors?`)) {
                    return;
                }

                try {
                    const response = await fetch('{{ route("log-notifier.errors.bulk") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            action: action,
                            ids: this.selectedErrors
                        })
                    });

                    const data = await response.json();
                    
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Failed to perform action');
                    }
                } catch (error) {
                    console.error('Bulk action failed:', error);
                    alert('Failed to perform action');
                }
            }
        };
    }
</script>
@endpush
@endsection
