<?php

namespace Domain\Portfolio\Controller;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use App\Services\ApiResponseService;
use Domain\Portfolio\Data\PortfolioCollectionResponseData;
use Domain\Portfolio\Data\PortfolioPayloadData;
use Domain\Portfolio\Data\PortfolioResponseData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class PortfolioController extends Controller
{
   public function show(
      Portfolio $portfolio,
      ApiResponseService $apiResponse
   ): JsonResponse {
      Gate::authorize('view', $portfolio);

      return $apiResponse->make(
         message: 'Portfolio fetched successfully.',
         status: Response::HTTP_OK,
         data: PortfolioResponseData::from($portfolio),
      );
   }

   public function index(Request $request, ApiResponseService $apiResponse): JsonResponse
   {
      $user = $request->user();

      $portfolios = Portfolio::query()
         ->where('user_id', $user->id)
         ->latest()
         ->get();

      $portfoliosData = PortfolioResponseData::collect(
         $portfolios
      );

      return $apiResponse->make(
         message: 'Portfolios fetched successfully.',
         status: Response::HTTP_OK,
         data: new PortfolioCollectionResponseData(portfolios: $portfoliosData->all()),
      );
   }

   public function store(
      PortfolioPayloadData $data,
      Request $request,
      ApiResponseService $apiResponse
   ): JsonResponse {
      $user = $request->user();

      $portfolio = Portfolio::query()->create([
         'name' => $data->name,
         'user_id' => $user->id,
      ]);

      return $apiResponse->make(
         message: 'Portfolio created successfully.',
         status: Response::HTTP_CREATED,
         data: PortfolioResponseData::from($portfolio),
      );
   }

   public function update(
      Portfolio $portfolio,
      PortfolioPayloadData $data,
      ApiResponseService $apiResponse
   ): JsonResponse {
      Gate::authorize('update', $portfolio);

      $portfolio->name = $data->name;
      $portfolio->save();

      return $apiResponse->make(
         message: 'Portfolio updated successfully.',
         status: Response::HTTP_OK,
         data: PortfolioResponseData::from($portfolio->fresh()),
      );
   }

   public function destroy(
      Portfolio $portfolio,
      ApiResponseService $apiResponse
   ): JsonResponse {
      Gate::authorize('delete', $portfolio);

      // When positions are introduced, deleting a portfolio must also remove its positions.
      $portfolio->delete();

      return $apiResponse->make(
         message: 'Portfolio deleted successfully.',
         status: Response::HTTP_OK,
      );
   }
}
