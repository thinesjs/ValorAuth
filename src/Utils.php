<?php
namespace Thinesjs\ValorAuth;
class Utils {
    public const jsonPath = ["version" => __DIR__ . "/json/version.json",
                             "buddies" => __DIR__ . "/json/buddies.json",
                             "bundles" => __DIR__ . "/json/bundles.json",
                             "competitivetiers" => __DIR__ . "/json/competitivetiers.json",
                             "maps" => __DIR__ . "/json/maps.json",
                             "playercards" => __DIR__ . "/json/playercards.json",
                             "playertitles" => __DIR__ . "/json/playertitles.json",
                             "seasons" => __DIR__ . "/json/seasons.json",
                             "contracts" => __DIR__ . "/json/contracts.json",
                             "skinchromas" => __DIR__ . "/json/skinchromas.json",
                             "skinlevels" => __DIR__ . "/json/skinlevels.json",
                             "skins" => __DIR__ . "/json/skins.json",
                             "sprays" => __DIR__ . "/json/sprays.json",
                             "weapons" => __DIR__ . "/json/weapons.json",
                             "agents" => __DIR__ . "/json/agents.json",
                             "offers" => __DIR__ . "/json/offers.json"];
                             
    public function getBetween($start, $end, $str){
        return explode($end,explode($start,$str)[1])[0];
    }

    public function ezDec($obj){
        if(gettype($obj) == "string") return json_decode($obj);
        if(gettype($obj) == "array") return json_encode($obj, JSON_FORCE_OBJECT);
    }

}
?>