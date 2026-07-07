<?php

namespace Webkul\Website\Filament\Admin\Widgets;

use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Webkul\Blog\Models\Post;

class BlogChart extends ChartWidget
{
    use InteractsWithPageFilters;

    public function getHeading(): ?string
    {
        return __('website::filament/admin/widgets/blog-chart.published-blogs-by-month');
    }

    protected ?string $maxHeight = '250px';

    protected static ?int $sort = 1;

    protected function getData(): array
    {
        $filters = $this->filters;

        $query = Post::query()->select(['created_at', 'is_published']);

        if (! empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        if (! empty($filters['author_id'])) {
            $query->where('author_id', $filters['author_id']);
        }

        // Bucketing by month is done in PHP (rather than SQL DATE_FORMAT/to_char)
        // so the query stays portable across database drivers.
        $postsByMonth = $query->get()->groupBy(fn (Post $post) => $post->created_at->format('Y-m'));

        $months = $postsByMonth->keys()->sort()->values();

        $publishedData = [];
        $draftData = [];
        $labels = [];

        foreach ($months as $monthKey) {
            $labels[] = Carbon::createFromFormat('Y-m', $monthKey)->format('M Y');

            $monthPosts = $postsByMonth->get($monthKey);

            $publishedData[] = $monthPosts->where('is_published', true)->count();

            $draftData[] = $monthPosts->where('is_published', false)->count();
        }

        // Return empty data message if no data available
        if (empty($labels)) {
            return [
                'datasets' => [
                    [
                        'label'           => __('website::filament/admin/widgets/blog-chart.published'),
                        'data'            => [0],
                        'backgroundColor' => 'rgba(76, 175, 80, 0.6)',
                        'borderColor'     => '#4CAF50',
                    ],
                    [
                        'label'           => __('website::filament/admin/widgets/blog-chart.draft'),
                        'data'            => [0],
                        'backgroundColor' => 'rgba(244, 67, 54, 0.6)',
                        'borderColor'     => '#F44336',
                    ],
                ],

                'labels' => [__('website::filament/admin/widgets/blog-chart.no-data-available')],
            ];
        }

        return [
            'datasets' => [
                [
                    'label'           => __('website::filament/admin/widgets/blog-chart.published'),
                    'data'            => $publishedData,
                    'backgroundColor' => 'rgba(76, 175, 80, 0.6)',
                    'borderColor'     => '#4CAF50',
                ],
                [
                    'label'           => __('website::filament/admin/widgets/blog-chart.draft'),
                    'data'            => $draftData,
                    'backgroundColor' => 'rgba(244, 67, 54, 0.6)',
                    'borderColor'     => '#F44336',
                ],
            ],

            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
