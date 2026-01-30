@extends('log-notifier::layouts.app')

@section('title', 'Error Details')

@section('content')
<div class="mb-6">
    <a href="{{ route('log-notifier.dashboard') }}" class="inline-flex items-center text-blue-600 hover:text-blue-800">
        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
        Back to Dashboard
    </a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-start">
        <div>
            <div class="flex items-center space-x-3 mb-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                    @if($error->level === 'emergency' || $error->level === 'critical') bg-red-100 text-red-800
                    @elseif($error->level === 'error') bg-yellow-100 text-yellow-800
                    @elseif($error->level === 'alert') bg-orange-100 text-orange-800
                    @else bg-gray-100 text-gray-800
                    @endif">
                    {{ strtoupper($error->level) }}
                </span>
                
                @if($error->is_resolved)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        ✓ Resolved
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                        ● Open
                    </span>
                @endif

                <span class="text-sm text-gray-500">
                    {{ $error->occurrence_count }} occurrence(s)
                </span>
            </div>
            <h1 class="text-xl font-semibold text-gray-900">{{ Str::limit($error->message, 100) }}</h1>
        </div>
        
        <div class="flex space-x-2">
            @if(!$error->is_resolved)
                <form action="{{ route('log-notifier.errors.resolve', $error->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                        Mark Resolved
                    </button>
                </form>
            @else
                <form action="{{ route('log-notifier.errors.unresolve', $error->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors">
                        Reopen
                    </button>
                </form>
            @endif
            
            <form action="{{ route('log-notifier.errors.destroy', $error->id) }}" method="POST" 
                  onsubmit="return confirm('Are you sure you want to delete this error?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                    Delete
                </button>
            </form>
        </div>
    </div>

    <!-- Error Details -->
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <p class="text-sm font-medium text-gray-500">First Occurred</p>
                <p class="mt-1 text-sm text-gray-900">{{ $error->first_occurred_at->format('Y-m-d H:i:s') }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Last Occurred</p>
                <p class="mt-1 text-sm text-gray-900">{{ $error->last_occurred_at->format('Y-m-d H:i:s') }}</p>
                <p class="text-xs text-gray-500">{{ $error->last_occurred_at->diffForHumans() }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Environment</p>
                <p class="mt-1 text-sm text-gray-900">{{ $error->environment ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Location</p>
                <p class="mt-1 text-sm text-gray-900 font-mono">{{ $error->location }}</p>
            </div>
        </div>
    </div>

    <!-- Message -->
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex justify-between items-center mb-2">
            <h2 class="text-lg font-medium text-gray-900">Error Message</h2>
            <button 
                onclick="copyToClipboard(document.getElementById('error-message').textContent)"
                class="text-sm text-blue-600 hover:text-blue-800"
            >
                Copy
            </button>
        </div>
        <div id="error-message" class="bg-gray-50 rounded-lg p-4 font-mono text-sm text-gray-800 whitespace-pre-wrap break-words">{{ $error->message }}</div>
    </div>

    <!-- Stack Trace -->
    @if($error->trace)
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center mb-2">
                <h2 class="text-lg font-medium text-gray-900">Stack Trace</h2>
                <button 
                    onclick="copyToClipboard(document.getElementById('stack-trace').textContent)"
                    class="text-sm text-blue-600 hover:text-blue-800"
                >
                    Copy
                </button>
            </div>
            <div id="stack-trace" class="bg-gray-900 rounded-lg p-4 font-mono text-sm text-gray-100 whitespace-pre-wrap break-words overflow-x-auto max-h-96 overflow-y-auto">{{ $error->trace }}</div>
        </div>
    @endif

    <!-- Context -->
    @if($error->context && count($error->context) > 0)
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900 mb-2">Context</h2>
            <div class="bg-gray-50 rounded-lg p-4">
                <pre class="text-sm text-gray-800">{{ json_encode($error->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </div>
    @endif

    <!-- Request Data -->
    @if($error->request_data && count($error->request_data) > 0)
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900 mb-2">Request Data</h2>
            <div class="bg-gray-50 rounded-lg p-4">
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($error->request_data as $key => $value)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ ucfirst(str_replace('_', ' ', $key)) }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ is_array($value) ? json_encode($value) : $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
    @endif

    <!-- Resolution Info -->
    @if($error->is_resolved)
        <div class="px-6 py-4 bg-green-50">
            <h2 class="text-lg font-medium text-green-800 mb-2">Resolution</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <p class="text-sm font-medium text-green-600">Resolved At</p>
                    <p class="mt-1 text-sm text-green-800">{{ $error->resolved_at?->format('Y-m-d H:i:s') }}</p>
                </div>
                @if($error->resolvedBy)
                    <div>
                        <p class="text-sm font-medium text-green-600">Resolved By</p>
                        <p class="mt-1 text-sm text-green-800">{{ $error->resolvedBy->name ?? 'User #' . $error->resolved_by }}</p>
                    </div>
                @endif
                @if($error->resolution_note)
                    <div>
                        <p class="text-sm font-medium text-green-600">Note</p>
                        <p class="mt-1 text-sm text-green-800">{{ $error->resolution_note }}</p>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            alert('Copied to clipboard!');
        }).catch(err => {
            console.error('Failed to copy:', err);
        });
    }
</script>
@endpush
@endsection
