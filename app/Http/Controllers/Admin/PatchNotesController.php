<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;

class PatchNotesController extends Controller
{
    public function index(Request $request)
    {
        $patchNotes = $this->loadPatchNotesFromCsv(
            public_path('patches/patches.csv')
        );

        $filters = [
            'q' => trim((string) $request->input('q', '')),
            'type' => trim((string) $request->input('type', '')),
            'status' => trim((string) $request->input('status', '')),
            'priority' => trim((string) $request->input('priority', '')),
        ];

        $patchNotes = array_values(array_filter($patchNotes, function (array $note) use ($filters) {
            if ($filters['q'] !== '') {
                $haystack = implode(' ', [
                    $note['ticket'] ?? '',
                    $note['title'] ?? '',
                    $note['summary'] ?? '',
                    $note['assignee'] ?? '',
                    $note['reporter'] ?? '',
                    $note['status'] ?? '',
                    $note['priority'] ?? '',
                ]);

                if (stripos($haystack, $filters['q']) === false) {
                    return false;
                }
            }

            foreach (['type', 'status', 'priority'] as $key) {
                if ($filters[$key] !== '' && strcasecmp((string) ($note[$key] ?? ''), $filters[$key]) !== 0) {
                    return false;
                }
            }

            return true;
        }));

        $typeOptions = collect($patchNotes)
            ->pluck('type')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $statusOptions = collect($patchNotes)
            ->pluck('status')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $priorityOptions = collect($patchNotes)
            ->pluck('priority')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $stats = [
            'notes' => count($patchNotes),
            'done' => collect($patchNotes)->where('status', 'Done')->count(),
            'testing' => collect($patchNotes)->where('status', 'FOR TESTING')->count(),
            'in_progress' => collect($patchNotes)->where('status', 'In Progress')->count(),
        ];

        return view('admin.pages.patch-notes.index', [
            'patchNotes' => $patchNotes,
            'stats' => $stats,
            'filters' => $filters,
            'filterOptions' => [
                'types' => $typeOptions,
                'statuses' => $statusOptions,
                'priorities' => $priorityOptions,
            ],
            'releasedAt' => Carbon::now()->format('F j, Y'),
        ]);
    }

    protected function loadPatchNotesFromCsv(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }

        $handle = fopen($path, 'r');

        if ($handle === false) {
            return [];
        }

        $headers = [];
        $notes = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (empty($headers)) {
                $headers = array_map(function ($header) {
                    return trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $header));
                }, $row);
                continue;
            }

            if (count($row) === 1 && trim((string) $row[0]) === '') {
                continue;
            }

            $record = [];
            foreach ($headers as $index => $header) {
                $record[$header] = trim((string) ($row[$index] ?? ''));
            }

            $createdAt = $this->parseCsvDate($record['Created'] ?? '');
            $updatedAt = $this->parseCsvDate($record['Updated'] ?? '');

            $notes[] = [
                'ticket' => $record['Issue key'] ?: 'N/A',
                'type' => $record['Issue Type'] ?: 'Task',
                'title' => $record['Summary'] ?: 'Untitled patch',
                'summary' => $this->buildSummary($record),
                'status' => $record['Status'] ?: 'Unknown',
                'priority' => $record['Priority'] ?: 'Normal',
                'resolution' => $record['Resolution'] ?: 'Pending',
                'reporter' => $record['Reporter'] ?: 'Unknown',
                'assignee' => $record['Assignee'] ?: 'Unassigned',
                'created_at' => $createdAt?->toIso8601String(),
                'updated_at' => $updatedAt?->toIso8601String(),
                'created_label' => $createdAt?->format('d M Y, g:i A') ?? ($record['Created'] ?: 'N/A'),
                'updated_label' => $updatedAt?->format('d M Y, g:i A') ?? ($record['Updated'] ?: 'N/A'),
                'tone' => $this->toneForStatus($record['Status'] ?? ''),
            ];
        }

        fclose($handle);

        usort($notes, function (array $a, array $b) {
            return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
        });

        return $notes;
    }

    protected function parseCsvDate(string $value): ?Carbon
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('d/M/y g:i A', $value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function buildSummary(array $record): string
    {
        $parts = [];

        if (!empty($record['Status'])) {
            $parts[] = "Status: {$record['Status']}";
        }

        if (!empty($record['Priority'])) {
            $parts[] = "Priority: {$record['Priority']}";
        }

        if (!empty($record['Resolution'])) {
            $parts[] = "Resolution: {$record['Resolution']}";
        }

        if (!empty($record['Created'])) {
            $parts[] = "Created: {$record['Created']}";
        }

        return implode(' • ', $parts) ?: 'Patch imported from CSV.';
    }

    protected function toneForStatus(string $status): string
    {
        return match (strtoupper(trim($status))) {
            'DONE' => 'emerald',
            'FOR TESTING' => 'sky',
            'IN PROGRESS' => 'amber',
            default => 'indigo',
        };
    }
}
