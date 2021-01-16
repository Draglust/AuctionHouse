<?php

namespace App\Handlers;

use Exception as GlobalException;
use App\Models\Reagent;
use Illuminate\Support\Facades\Cache;

class ReagentHandler
{

    public function saveReagentData($data){
        try{
            $reagent_already_inserted = Reagent::where('recipe_id', '=', $data->recipe_id)->where('item_id', '=', $data->item_id)->get();

            if($reagent_already_inserted->count() == 0){
                $reagent = new Reagent;
                $reagent->item_id = $data->item_id;
                $reagent->recipe_id = $data->recipe_id;
                $reagent->quantity = $data->quantity;
                $reagent->save();

                unset($reagent);
            }
            unset($reagent_already_inserted);

            return true;
        }
        catch(GlobalException $e){
            dd($data);
        }
        return false;
    }

    public function getReagentByRecipeId($id){
        $reagents = Reagent::where('recipe_id', '=', $id)->get();

        $reagents_list = [];
        if($reagents->count() != 0){
            $reagents_list = $reagents->toArray();
        }

        return $reagents_list;
    }

}
