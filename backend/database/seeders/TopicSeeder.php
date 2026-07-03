<?php

namespace Database\Seeders;

use App\Models\Level;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Database\Seeder;

class TopicSeeder extends Seeder
{
    /**
     * Core Cameroon GCE Biology syllabus topics. A curated subset per level -
     * enough breadth to exercise the adaptive engine and GCE simulation
     * end-to-end; teachers/admins extend via the question/topic CRUD endpoints.
     */
    public function run(): void
    {
        $subject = Subject::where('name', 'Biology')->firstOrFail();

        $oLevel = Level::where('code', 'o_level')->firstOrFail();
        $aLevel = Level::where('code', 'a_level')->firstOrFail();

        $oLevelTopics = [
            ['code' => 'OL-CELL', 'name' => 'Cell Structure and Organisation', 'exam_weight' => 3],
            ['code' => 'OL-TRANSPORT-CELL', 'name' => 'Movement of Substances Into and Out of Cells', 'exam_weight' => 2],
            ['code' => 'OL-PLANT-NUTRITION', 'name' => 'Nutrition in Plants (Photosynthesis)', 'exam_weight' => 3],
            ['code' => 'OL-ANIMAL-NUTRITION', 'name' => 'Nutrition in Animals (Human Digestion)', 'exam_weight' => 3],
            ['code' => 'OL-TRANSPORT-ANIMAL', 'name' => 'Transport in Animals (Circulatory System)', 'exam_weight' => 2],
            ['code' => 'OL-RESPIRATION', 'name' => 'Respiration', 'exam_weight' => 2],
            ['code' => 'OL-EXCRETION', 'name' => 'Excretion', 'exam_weight' => 1],
            ['code' => 'OL-COORDINATION', 'name' => 'Coordination and Response', 'exam_weight' => 2],
            ['code' => 'OL-REPRODUCTION', 'name' => 'Reproduction in Humans', 'exam_weight' => 2],
            ['code' => 'OL-ECOLOGY', 'name' => 'Ecology and the Environment', 'exam_weight' => 2],
        ];

        $aLevelTopics = [
            ['code' => 'AL-CELL', 'name' => 'Cell Structure and Ultrastructure', 'exam_weight' => 3],
            ['code' => 'AL-BIOMOLECULES', 'name' => 'Biological Molecules and Enzymes', 'exam_weight' => 3],
            ['code' => 'AL-CELL-DIVISION', 'name' => 'Cell Division (Mitosis and Meiosis)', 'exam_weight' => 2],
            ['code' => 'AL-GENETICS', 'name' => 'Genetics and Inheritance', 'exam_weight' => 3],
            ['code' => 'AL-MOLECULAR-GENETICS', 'name' => 'Molecular Genetics (DNA and Protein Synthesis)', 'exam_weight' => 2],
            ['code' => 'AL-HOMEOSTASIS', 'name' => 'Homeostasis and Excretion', 'exam_weight' => 2],
            ['code' => 'AL-COORDINATION', 'name' => 'Nervous and Hormonal Coordination', 'exam_weight' => 2],
            ['code' => 'AL-GAS-EXCHANGE', 'name' => 'Gaseous Exchange and Respiration', 'exam_weight' => 2],
            ['code' => 'AL-ECOLOGY', 'name' => 'Ecology and Biodiversity', 'exam_weight' => 2],
            ['code' => 'AL-EVOLUTION', 'name' => 'Evolution and Natural Selection', 'exam_weight' => 1],
        ];

        foreach ($oLevelTopics as $order => $topic) {
            Topic::updateOrCreate(
                ['code' => $topic['code']],
                [
                    'subject_id' => $subject->id,
                    'level_id' => $oLevel->id,
                    'name' => $topic['name'],
                    'exam_weight' => $topic['exam_weight'],
                    'order' => $order,
                ]
            );
        }

        foreach ($aLevelTopics as $order => $topic) {
            Topic::updateOrCreate(
                ['code' => $topic['code']],
                [
                    'subject_id' => $subject->id,
                    'level_id' => $aLevel->id,
                    'name' => $topic['name'],
                    'exam_weight' => $topic['exam_weight'],
                    'order' => $order,
                ]
            );
        }
    }
}
