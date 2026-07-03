<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public-safe question payload served while a quiz is in progress -
 * deliberately omits correct_option and explanation to prevent answer leakage.
 */
class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'topic_id' => $this->topic_id,
            'level_id' => $this->level_id,
            'stem' => $this->stem,
            'options' => $this->options,
            'difficulty' => $this->difficulty,
            'marks' => $this->marks,
        ];
    }
}
