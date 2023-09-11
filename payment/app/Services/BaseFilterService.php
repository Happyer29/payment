<?php

namespace App\Services;

use Illuminate\Http\Request;

abstract class BaseFilterService
{

    private string $model;

    public function getFilters(string $action, Request $request): array
    {
        $res = [];

        $filters = $this->getDefaultFilters();
        $this->addSupportedFilters($filters);

        foreach($filters as $name => $filter){
            $actions = $filter['actions'] ?? null;
            if($actions and !empty($actions)){
                $actions = is_array($actions) ? $actions : [$actions];
                if(!in_array($action, $actions)){
                    continue;
                }
            }

            if(isset($filter['can']) and !empty($filter['can'])){
                $can = is_array($filter['can']) ? $filter['can'] : [$filter['can']];
                try{
                    if(!$request->user()?->can(...$can)){
                        continue;
                    }
                }catch (\Throwable $ex){
                    continue;
                }
            }

            $filter['name'] = $name;

            if($request->has($filter['name'])){
                $filter['value'] = $request->input($filter['name']);
            }elseif($filter['default'] ?? false){
                $filter['value'] = $filter['default'];
            }

            $res[$name] = $filter;
        }
        return $res;
    }

    public function getDefaultFilters(): array
    {
        return [];
    }

    public function addSupportedFilters(array &$filters): void
    {
        $supported = $this->supportedFilters();
        if(in_array('page', $supported) and !isset($filters['page'])){
            $filters['page'] = [
                'type' => 'hidden',
                'default' => 1,
            ];
        }
        if(in_array('size', $supported) and !isset($filters['size'])){
            $filters['size'] = [
                'type' => 'hidden',
                'default' => 30,
            ];
        }
    }

    public function supportedFilters(): array
    {
        return ['page', 'size'];
    }

    public function getModelClass(): string
    {
        return $this->model;
    }

    public function getBulkActions(Request $request): array
    {
        $res = [];
        $default = $this->getDefaultBulkActions();
        $selected = null;
        foreach ($default as $key => $bulk){
            if(isset($bulk['can']) and !empty($bulk['can'])){
                $can = is_array($bulk['can']) ? $bulk['can'] : [$bulk['can']];
                try{
                    if(!$request->user()?->can(...$can)){
                        continue;
                    }
                }catch (\Throwable $ex){
                    continue;
                }
            }

            $res[$key] = $bulk;
        }

        $bulk_confirm = intval($request->input('bulk_confirm'));
        if($bulk_confirm < time() and $bulk_confirm + 3600 > time()){
            $bulk_action = $request->input('bulk_action');
            if($bulk_action and isset($res[$bulk_action])){
                $res[$bulk_action]['selected'] = true;
                $selected = $bulk_action;
            }
        }

        $items = $request->input('items');
        if(!empty($items)){
            $items = array_map('intval', explode(',', $items));
            $items = array_filter($items, fn($x) => $x > 0);
            if(empty($items)){
                $items = null;
            }
        }else{
            $items = null;
        }

        return [
            'actions' => $res,
            'selected' => $selected,
            'items' => $items,
        ];
    }

    public function getDefaultBulkActions(): array
    {
        return [];
    }

}
