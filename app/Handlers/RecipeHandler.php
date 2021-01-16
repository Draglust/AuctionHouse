<?php

namespace App\Handlers;

use Exception as GlobalException;
use App\Handlers\EndpointHandler as EndpointHandler;
use App\Models\Recipe;
use Illuminate\Support\Facades\Cache;

class RecipeHandler
{

    public function saveRecipeData($data){
        try{
            $recipe_already_inserted = Recipe::where('id', '=', $data->id)->get();

            if($recipe_already_inserted->count() == 0){
                $recipe = new Recipe;
                $recipe->id = $data->id;
                $recipe->profession = $data->profession;
                $recipe->tier_name = $data->tier_name;
                $recipe->category_name = $data->category_name;
                $recipe->recipe_name = $data->recipe_name;
                $recipe->recipe_url = $data->recipe_url;
                $recipe->crafted_item_id = $data->crafted_item_id;
                $recipe->save();

                unset($recipe);
            }
            unset($recipe_already_inserted);

            return true;
        }
        catch(GlobalException $e){
            dd($data);
        }
        return false;
    }

    public function getRecipeDataGivenUrl($recipe_url){
        $endpoint_handler = new EndpointHandler;

        $recipe_data = Cache::store('file')->get($recipe_url) ?? $endpoint_handler->genericBlizzardConnection($recipe_url);
        if(empty($recipe_data['body'])){
            $endpoint_handler->refreshToken();
            $recipe_data = $endpoint_handler->genericBlizzardConnection($recipe_url);
        }
        Cache::store('file')->put($recipe_url, $recipe_data, 3600);

        return $recipe_data;
    }

    public function getNotInsertedRecipes($recipes_array){
        $recipe_urls = Recipe::pluck('recipe_url')->unique();
        $recipe_urls = $recipe_urls->toArray();
        $not_inserted_recipes = [];
        foreach($recipes_array as $recipe){
            if(!in_array($recipe->key->href, $recipe_urls)){
                $not_inserted_recipes[] = $recipe;
            }
        }
        return $not_inserted_recipes;
    }

    public function getAllRecipes(){
        $recipes = Recipe::all()->toArray();

        return $recipes;
    }


}
