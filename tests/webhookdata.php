<?php

$lastGlobalVersion = 5581559;
$baseID = 98272;


$apiResponseRaw = json_decode('{
  "winstrom": {
    "@globalVersion": "'.$lastGlobalVersion.'",
    "changes": [
      {
        "@evidence": "banka",
        "@in-version": "'.($lastGlobalVersion-2).'",
        "@operation": "create",
        "@timestamp": "2022-02-21 22:12:47.063552",
        "id": "'.$baseID.'",
        "external-ids": [
          "code:B+00010636/24"
        ]
      }
    ],
    "next": "none"
  }
}', true);
