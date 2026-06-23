<?php

namespace Database\Seeders;

use App\Models\AppBanner;
use Illuminate\Database\Seeder;

class AppBannerSeeder extends Seeder
{
    public function run(): void
    {
        $banners = [
            [
                'title' => 'Start Your Transformation',
                'subtitle' => 'Join today and unlock premium workouts',
                'banner_type' => 'Home Banner',
                'target_audience' => 'All Users',
                'button_text' => 'Start Now',
                'button_action' => 'Open Workouts',
                'action_url' => '/workouts',
                'display_order' => 1,
                'publish_status' => 'Published',
                'show_in_mobile_app' => true,
                'priority' => 'Featured',
                'allow_dismiss' => true,
                'background_style' => 'Gradient',
                'text_alignment' => 'Left',
                'background_color' => '#050505',
                'accent_color' => '#C8A13A',
                'description' => 'Primary home screen banner for members opening the mobile app.',
            ],
            [
                'title' => 'Book Today\'s Class',
                'subtitle' => 'Reserve a spot before sessions fill up',
                'banner_type' => 'Class Banner',
                'target_audience' => 'Members Only',
                'button_text' => 'Book Class',
                'button_action' => 'Open Classes',
                'action_url' => '/classes',
                'display_order' => 2,
                'publish_status' => 'Published',
                'show_in_mobile_app' => true,
                'priority' => 'High',
                'allow_dismiss' => true,
                'background_style' => 'Gradient',
                'text_alignment' => 'Left',
                'background_color' => '#080808',
                'accent_color' => '#C8A13A',
                'description' => 'Promotes active class booking in the mobile app.',
            ],
            [
                'title' => 'Fuel Your Progress',
                'subtitle' => 'Follow diet plans built around your fitness goal',
                'banner_type' => 'Diet Banner',
                'target_audience' => 'Premium Users',
                'button_text' => 'View Diets',
                'button_action' => 'Open Diet Plans',
                'action_url' => '/diet',
                'display_order' => 3,
                'publish_status' => 'Draft',
                'show_in_mobile_app' => false,
                'priority' => 'Normal',
                'allow_dismiss' => true,
                'background_style' => 'Solid Color',
                'text_alignment' => 'Left',
                'background_color' => '#111111',
                'accent_color' => '#C8A13A',
                'description' => 'Draft banner for nutrition plan promotion.',
            ],
        ];

        foreach ($banners as $banner) {
            AppBanner::query()->updateOrCreate(
                ['title' => $banner['title']],
                $banner,
            );
        }
    }
}
