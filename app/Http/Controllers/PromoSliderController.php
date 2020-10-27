<?php

namespace App\Http\Controllers;

use App\PromoSlider;
use App\Slide;
use Cache;
use Illuminate\Http\Request;
use Modules\SuperCache\SuperCache;
use Nwidart\Modules\Facades\Module;

class PromoSliderController extends Controller
{
    /**
     * @param $name
     * @param $data
     */
    private function processSuperCache($name, $data = null)
    {
        if (Module::find('SuperCache') && Module::find('SuperCache')->isEnabled()) {
            $superCache = new SuperCache();
            $superCache->cacheResponse($name, $data);
        }
    }
    /**
     * @param Request $request
     */
    public function promoSlider(Request $request)
    {
        // Cache::forget('main-slider');
        // Cache::forget('other-position-slider');

        if (Cache::has('main-slider')) {
            $mainSlider = Cache::get('main-slider');
        } else {
            $mainSlider = PromoSlider::where('position_id', 0)->where('is_active', 1)->first();
            $this->processSuperCache('main-slider', $mainSlider);
        }

        if (Cache::has('other-position-slider')) {
            $otherPosition = Cache::get('other-position-slider');
        } else {
            $otherPosition = PromoSlider::whereIn('position_id', [1, 2, 3, 4, 5, 6])->where('is_active', 1)->first();
            $this->processSuperCache('other-position-slider', $otherPosition);
        }

        // $otherPosition = PromoSlider::whereIn('position_id', [1, 2, 3, 4, 5, 6])->where('is_active', 1)->first();

        // if present then return that for all locations
        if ($mainSlider) {

            // Cache::forget('main-slider-slides-' . $mainSlider->id);

            if (Cache::has('main-slider-slides-' . $mainSlider->id)) {
                $mainSlides = Cache::get('main-slider-slides-' . $mainSlider->id);
            } else {
                $mainSlides = Slide::where('promo_slider_id', $mainSlider->id)
                    ->where('is_active', '1')
                    ->with('promo_slider')
                    ->ordered()
                    ->get();
                $this->processSuperCache('main-slider-slides-' . $mainSlider->id, $mainSlides);
            }

        } else {
            $mainSlides = null;
        }

        if ($otherPosition) {

            // Cache::forget('other-position-slides-' . $otherPosition->id)

            if (Cache::has('other-position-slides-' . $otherPosition->id)) {
                $otherSlides = Cache::get('other-position-slides-' . $otherPosition->id);
            } else {
                $otherSlides = Slide::where('promo_slider_id', $otherPosition->id)
                    ->where('is_active', '1')
                    ->with('promo_slider')
                    ->ordered()
                    ->get();
                $this->processSuperCache('other-position-slides-' . $otherPosition->id, $otherSlides);
            }

        } else {
            $otherSlides = null;
        }

        $response = [
            'mainSlides' => $mainSlides,
            'otherSlides' => $otherSlides,
        ];
        return response()->json($response);
    }
}
