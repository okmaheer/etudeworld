<?php

namespace Database\Seeders;

use App\Models\LevelTest;
use Illuminate\Database\Seeder;

/** Sample 30-question English Level Test (from the CEFR placement test PDF). Safe to run once; skips if the test exists. */
class LevelTestSeeder extends Seeder
{
    public function run(): void
    {
        if (LevelTest::where('slug', 'english-level-test')->exists()) {
            return;
        }
        $test = LevelTest::create([
            'title' => 'English Level Test',
            'slug' => 'english-level-test',
            'instructions' => "Answer 30 questions to find your English level from A1 to C2. It takes about 3 minutes.\nDo not use a dictionary or any other help. If you are not sure, take your best guess.",
            'meta_title' => 'Free English Level Test (A1 to C2) – 3 Minutes',
            'meta_description' => 'Check your English level in about 3 minutes. Free 30-question test with vocabulary, grammar and reading. Get your CEFR level from A1 to C2 and see the correct answers.',
            'status' => 'active',
        ]);

        $mcq = fn ($level, $prompt, $opts, $ans, $passage = null) => ['mcq', $level, $prompt, $opts, $ans, $passage];

        $s1 = [
            $mcq('A1', 'What is the opposite of "hot"?', ['warm', 'cold', 'wet', 'big'], 1),
            $mcq('A2', '"I need to ___ a doctor — I feel very ill." Choose the best word.', ['see', 'watch', 'look', 'view'], 0),
            $mcq('A2', '"She was very ___ when she passed her exam — she smiled all day." Which word fits?', ['angry', 'delighted', 'tired', 'worried'], 1),
            $mcq('B1', '"The government announced new ___ to reduce pollution." Which word fits?', ['measures', 'meters', 'ways', 'things'], 0),
            $mcq('B1', '"The meeting has been ___ until next week." Choose the correct word.', ['cancelled', 'postponed', 'finished', 'stopped'], 1),
            $mcq('B2', '"The new policy will ___ the rights of workers across the country." Which word fits?', ['safeguard', 'watch', 'look', 'keep'], 0),
            $mcq('B2', '"Her argument was very ___ — it was impossible to disagree with her logic."', ['compelling', 'talking', 'saying', 'noisy'], 0),
            $mcq('C1', '"The prime minister\'s speech was designed to ___ public anxiety about the economy."', ['alleviate', 'increase', 'produce', 'find'], 0),
            $mcq('C1', 'Choose the word closest in meaning to "ubiquitous".', ['rare', 'everywhere', 'useful', 'ancient'], 1),
            $mcq('C2', '"Her calm expression ___ the panic she felt inside." Choose the correct word.', ['belied', 'revealed', 'showed', 'expressed'], 0),
        ];
        $s2 = [
            $mcq('A1', '"___ you from France?" Choose the correct form.', ['Is', 'Are', 'Am', 'Do'], 1),
            $mcq('A2', '"When I was young, I ___ in Paris."', ['lived', 'have lived', 'am living', 'was lived'], 0),
            $mcq('A2', '"This is ___ expensive hotel I have ever stayed in."', ['more', 'most', 'the most', 'the more'], 2),
            $mcq('B1', '"I ___ this company for five years." (I still work here.)', ['worked', 'have worked', 'was working', 'had worked'], 1),
            $mcq('B1', '"If it ___ tomorrow, we will cancel the match."', ['rains', 'will rain', 'rained', 'would rain'], 0),
            $mcq('B2', '"If I ___ you, I would apologise immediately."', ['am', 'was', 'were', 'had been'], 2),
            $mcq('B2', '"By the time she arrived, the meeting ___ already."', ['finished', 'has finished', 'had finished', 'was finishing'], 2),
            $mcq('C1', '"It is essential that every student ___ the assignment on time."', ['submits', 'submit', 'submitted', 'will submit'], 1),
            $mcq('C1', '"Not only ___ he fail the exam, but he also missed all the revision sessions."', ['did', 'had', 'was', 'has'], 0),
            $mcq('C2', '"The manager insisted that the new policy ___ without delay."', ['implemented', 'is implemented', 'be implemented', 'was implemented'], 2),
        ];
        $s3 = [
            $mcq('A1', 'How old is Carla?', ['10', '12', '14', '16'], 1, 1),
            $mcq('A1', "What is Carla's father's job?", ['A doctor', 'A teacher', 'A taxi driver', 'A nurse'], 2, 1),
            $mcq('A2', 'What does the family do at weekends?', ['They stay at home.', 'They go to the cinema.', 'They go to the park.', 'They visit grandparents.'], 2, 1),
            $mcq('A2', 'Which statement is TRUE according to the text?', ["Carla's mum works as a taxi driver.", "Miguel's favourite subject is English.", "Carla's dad works during the day.", 'The family has a black dog called Bruno.'], 3, 1),
            $mcq('B1', 'Before 2020, remote working was mainly available to:', ['All workers equally.', 'A small number of workers, mostly in tech.', 'Only senior employees.', 'Government workers.'], 1, 2),
            $mcq('B1', 'According to the text, which is an advantage of remote work?', ['More social interaction.', 'Better access to mentoring.', 'Reduced commuting time.', 'More structured hours.'], 2, 2),
            $mcq('B2', 'The word "proponents" (paragraph 1) is closest in meaning to:', ['critics', 'supporters', 'researchers', 'managers'], 1, 2),
            $mcq('B2', 'Which group is specifically mentioned as facing a disadvantage from remote work?', ['Senior managers', 'Technology workers', 'Junior employees', 'Part-time workers'], 2, 2),
            $mcq('B2', 'Find the ONE error: "Despite of the heavy rain, the match continued."', ['Despite of → Despite', 'heavy → heavier', 'continued → was continued', 'No error'], 0),
            $mcq('C1', 'Find the ONE error: "It is vital that every delegate submits their report before the deadline."', ['It is vital → It is vitally', 'submits → submit', 'their report → his report', 'No error'], 1),
        ];

        $sections = [
            1 => ['Choose the word or phrase that best completes the sentence or best matches the meaning.', $s1, null],
            2 => ['Choose the grammatically correct answer.', $s2, null],
            3 => ['Read each passage and answer the questions. Then find the ONE grammatical error in each sentence, or choose "No error" if the sentence is correct.', $s3, [
                1 => ['title' => 'My Family', 'body' => 'My name is Carla. I am twelve years old. I live in a small flat in Lisbon with my family. There are four people in my family: my mum, my dad, my older brother Miguel, and me. My dad is a taxi driver — he works at night. My mum is a nurse at a local hospital. Miguel is sixteen and his favourite subject is Maths. At weekends we all go to the park with our dog, Bruno, who is a big black dog that loves running.'],
                2 => ['title' => 'The Rise of Remote Work', 'body' => 'The COVID-19 pandemic fundamentally transformed the way millions of people work. Before 2020, remote working was enjoyed mainly by a small minority of employees in technology-related fields. By mid-2020, however, approximately 40% of the workforce in many developed countries was working from home. Proponents argue that remote work offers reduced commuting time, lower costs, and greater flexibility. Studies suggest that many remote workers report higher productivity and job satisfaction.' . "\n\n" . 'However, the shift has not been without challenges. Many employees struggle with isolation, blurred boundaries between professional and personal life, and a lack of adequate home workspace. Junior employees in particular report missing the informal learning and mentoring that happens naturally in an office. Most organisations are now adopting a hybrid model — a combination of office and remote work — to balance the advantages of both.'],
            ]],
        ];

        foreach ($sections as $n => [$instr, $questions, $passages]) {
            $sec = $test->section($n);
            $sec->update(['instructions' => $instr, 'passages' => $passages]);
            foreach ($questions as $i => [$type, $level, $prompt, $opts, $ans, $passage]) {
                $sec->questions()->create([
                    'passage' => $passage, 'position' => $i + 1, 'type' => $type, 'prompt' => $prompt,
                    'options' => $opts, 'answer' => (string) $ans, 'level' => $level,
                ]);
            }
        }
    }
}
