<?php 
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

function recursiveClone(Collection $collection)
{
    return $collection->map(function ($item) {


        if (is_array($item)) {
            return array_map('recursiveClone', $item);
        }

        if ($item instanceof Model) {
            // Clone the model itself
            $clonedItem = clone $item;

            // Get the relationships of the model
            $relations = $clonedItem->getRelations();

            // Recursively clone each relationship
            foreach ($relations as $relationName => $relationValue) {
                if ($relationValue instanceof Collection) {
                    // Recursively clone nested collections
                    $clonedItem->setRelation($relationName, recursiveClone($relationValue));
                } elseif ($relationValue instanceof Model) {
                    // Clone the related model
                    $clonedItem->setRelation($relationName, clone $relationValue);
                }
            }

            return $clonedItem;
        }


        // If the item is not a model, return it as is (or clone if necessary)

        if (is_object($item)) {
            $clonedItem = clone $item;
            foreach ($clonedItem as $key => $value) {
                $clonedItem->$key = recursiveClone($value);
            }
            return $clonedItem;
        }


        return  $item;
    });
}
