<?php

class MLAnomalyService {

    public static function score(array $d): float {
        $p = proc_open("python3 /opt/ml/ml_score.py",
            [["pipe","r"],["pipe","w"],["pipe","w"]], $pipes);

        fwrite($pipes[0], json_encode($d));
        fclose($pipes[0]);

        $score = floatval(trim(stream_get_contents($pipes[1])));
        proc_close($p);

        return $score;
    }

}
