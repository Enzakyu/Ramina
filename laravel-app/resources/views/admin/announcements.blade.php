@extends('layouts.app')

@section('title', 'Company Announcements')

@section('content')
<div class="d-flex justify-content-end mb-4">
    <button class="btn btn-primary" onclick="document.getElementById('addAnnouncementModal').style.display='flex'">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        New Announcement
    </button>
</div>

<div class="row">
    @forelse($announcements as $announcement)
        <div class="col-md-6 mb-4">
            <div class="card animate-slide-in">
                <div class="d-flex justify-content-between">
                    <h5 style="margin: 0; font-weight: 600;">{{ $announcement['title'] }}</h5>
                    <span class="badge bg-primary">{{ \Carbon\Carbon::parse($announcement['date'])->format('d M Y') }}</span>
                </div>
                <hr>
                <div style="color: var(--text-secondary); line-height: 1.6;">
                    {!! $announcement['content'] !!}
                </div>
                <div class="mt-4 text-end">
                    <form action="{{ route('admin.announcements.destroy', $announcement['id']) }}" method="POST" onsubmit="showLoading()">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: none;">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12 text-center py-5" style="color: var(--text-secondary);">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" class="mb-3"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
            <p>No announcements published yet.</p>
        </div>
    @endforelse
</div>

<!-- Add Modal -->
<div id="addAnnouncementModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 100; align-items: center; justify-content: center;">
    <div class="card animate-fade-in" style="width: 100%; max-width: 500px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 style="margin: 0; font-weight: 600;">Publish Announcement</h4>
            <button class="btn btn-light" style="padding: 0.5rem;" onclick="document.getElementById('addAnnouncementModal').style.display='none'">✕</button>
        </div>
        <form action="{{ route('admin.announcements.store') }}" method="POST" onsubmit="showLoading()">
            @csrf
            <div class="form-group mb-3">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control" required placeholder="e.g. Q3 Townhall Meeting">
            </div>
            <div class="form-group mb-4">
                <label class="form-label">Message Content</label>
                <textarea name="content" class="form-control" rows="5" required placeholder="Write your announcement here..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary w-100">Publish to Dashboard</button>
        </form>
    </div>
</div>

<script>
function showLoading() {
    if(document.getElementById('global-loader')) {
        document.getElementById('global-loader').style.display = 'flex';
    }
}
</script>
@endsection
