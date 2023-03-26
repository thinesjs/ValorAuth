<?php
namespace Thinesjs\ValorAuth;
class Utils {                
    public function getBetween($start, $end, $str){
        return explode($end,explode($start,$str)[1])[0];
    }
}
?>