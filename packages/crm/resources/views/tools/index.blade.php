@extends('crm::layouts.app')

@section('title', 'CRM Tools') @section('heading', 'CRM Tools') @section('subtitle',
'Backups,
imports, exports, and platform connections.')

@section('content')@include('crm::partials.tools-tabs')
    <section class="crm-card">
        <div class="crm-actions">
            <h2 style="margin-right:auto">Database Backups</h2>
            <form method="POST" action="{{ route('crm.tools.backups.run') }}">
                @csrf<button class="crm-button crm-button-primary" @disabled(!$isSpatieBackupInstalled)>Run
                    Backup</button>
            </form>
        </div>
        @if (!$isSpatieBackupInstalled)
            <p class="crm-flash-error crm-flash">Spatie Backup is not installed.</p>
        @endif
        <div class="crm-table-wrap">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Size</th>
                        <th>Date</th>
                        <th>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($backups as $backup)
                        <tr>
                            <td>{{ data_get($backup, 'name', data_get($backup, 'path')) }}</td>
                            <td>{{ data_get($backup, 'size_label', data_get($backup, 'size')) }}</td>
                            <td>{{ data_get($backup, 'created_at_label', data_get($backup, 'date')) }}
                            </td>
                            <td>
                                @if (data_get($backup, 'download_url'))
                                    <a href="{{ $backup['download_url'] }}">Download</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="crm-empty">No backups found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
