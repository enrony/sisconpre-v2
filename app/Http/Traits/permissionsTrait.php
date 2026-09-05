<?php

namespace App\Http\Traits;

trait permissionsTrait
{
    public static function selectModulesUserAccess($moulesUser, $modules)
    {

        if (count($moulesUser[0]) > 0) {
            $modules = $modules->filter(function ($module) use ($moulesUser) {
                if (in_array($module['id'], $moulesUser[0]) || $module['id'] == 1 || $module['id'] == 19) {

                    return $module;
                } else {

                    if (count($module['children']) > 0) {
                        $opcionesConAcceso = $module['children']->filter(function ($chil2) use ($moulesUser) {
                            if (in_array($chil2->id, $moulesUser[0])) {
                                return $chil2;
                            }
                        });

                        if (count($opcionesConAcceso) > 0) {
                            $module['children'] = $opcionesConAcceso;

                            return $module;
                        }

                    }

                    // while ($children->children) {

                    //     $ids = $module->children->filter( function( $chil2 ) use($moulesUser){
                    //         if( in_array( $chil2->id, $moulesUser[0]) ){
                    //             return $chil2;
                    //         }
                    //     });

                    //     if( count( $ids ) > 0 ){
                    //         return $module;
                    //     }

                    // }
                }
            });
        } else {
            $modules = $modules->filter(function ($module) {
                if ($module['id'] == 1 || $module['id'] == 19) {// Dashboard
                    return $module;
                }
            });
        }

        return $modules;

    }

    public static function modulesUserAccess($request)
    {

        $access = collect([]);
        $accessParent = [];

        for ($i = 0; $i < count($request->user()->profiles); $i++) {
            if ($request->user()->profiles[$i]->profile && $request->user()->profiles[$i]->profile->modules_actions_profiles) {
                $access = $access->merge($request->user()->profiles[$i]->profile->modules_actions_profiles->pluck('modules_actions_id'));
                foreach ($request->user()->profiles[$i]->profile->modules_actions_profiles as $modules_actions_profiles) {
                    if (! in_array($modules_actions_profiles->module_actions->modules_id, $accessParent)) {
                        $accessParent[] = $modules_actions_profiles->module_actions->modules_id;
                    }
                }
            }
        }

        return [$accessParent, $access];

    }

    public static function isUserAdmin($request)
    {
        $isAdmin = $request->user()->profiles->filter(function ($profile) {
            if ($profile->profile && $profile->profile->admin) {
                return $profile;
            }
        });

        if (count($isAdmin) > 0) {// Si es admiistrador general tiene acceso a todo
            return true;
        }

        return false;

    }
}
