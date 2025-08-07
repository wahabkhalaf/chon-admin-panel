<?php

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\CompetitionQuestion;
use App\Models\PrizeTier;
use App\Models\Question;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompetitionFullSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        Competition::truncate();
        CompetitionQuestion::truncate();
        Question::truncate();
        PrizeTier::truncate();

        // Create questions first
        $this->createQuestions();

        // Get all questions for competitions
        $questions = Question::where('question_type', 'multi_choice')->get();

        // Define competition configurations
        $competitions = [
            [
                'name' => 'Trivia Blast',
                'description' => 'Test your knowledge with these trivia questions covering various topics from history to pop culture!',
                'entry_fee' => 5.00,
                'max_users' => 100,
                'game_type' => 'multi_choice',
                'question_count' => 5,
                'prize_tiers' => [
                    ['rank_from' => 1, 'rank_to' => 1, 'prize_type' => 'cash', 'prize_value' => 250.00],
                    ['rank_from' => 2, 'rank_to' => 2, 'prize_type' => 'cash', 'prize_value' => 100.00],
                    ['rank_from' => 3, 'rank_to' => 3, 'prize_type' => 'cash', 'prize_value' => 50.00],
                ]
            ],
            [
                'name' => 'Knowledge Quest',
                'description' => 'Challenge yourself with these multiple choice questions covering science, history, and more!',
                'entry_fee' => 3.00,
                'max_users' => 200,
                'game_type' => 'multi_choice',
                'question_count' => 5,
                'prize_tiers' => [
                    ['rank_from' => 1, 'rank_to' => 1, 'prize_type' => 'cash', 'prize_value' => 300.00],
                    ['rank_from' => 2, 'rank_to' => 10, 'prize_type' => 'points', 'prize_value' => 50.00],
                ]
            ],
            [
                'name' => 'Brain Teaser',
                'description' => 'Test your analytical thinking with these challenging multiple choice questions!',
                'entry_fee' => 10.00,
                'max_users' => 50,
                'game_type' => 'multi_choice',
                'question_count' => 5,
                'prize_tiers' => [
                    ['rank_from' => 1, 'rank_to' => 3, 'prize_type' => 'cash', 'prize_value' => 150.00],
                    ['rank_from' => 4, 'rank_to' => 10, 'prize_type' => 'points', 'prize_value' => 100.00],
                ]
            ],
            [
                'name' => 'Quick Quiz',
                'description' => 'Fast-paced multiple choice questions that test your speed and knowledge!',
                'entry_fee' => 2.00,
                'max_users' => 500,
                'game_type' => 'multi_choice',
                'question_count' => 5,
                'prize_tiers' => [
                    ['rank_from' => 1, 'rank_to' => 1, 'prize_type' => 'cash', 'prize_value' => 500.00],
                ]
            ],
            [
                'name' => 'General Knowledge',
                'description' => 'Put your general knowledge to the test with these multiple choice questions!',
                'entry_fee' => 7.00,
                'max_users' => 80,
                'game_type' => 'multi_choice',
                'question_count' => 5,
                'prize_tiers' => [
                    ['rank_from' => 1, 'rank_to' => 1, 'prize_type' => 'cash', 'prize_value' => 200.00],
                    ['rank_from' => 2, 'rank_to' => 3, 'prize_type' => 'cash', 'prize_value' => 75.00],
                    ['rank_from' => 4, 'rank_to' => 5, 'prize_type' => 'points', 'prize_value' => 50.00],
                ]
            ],
        ];

        // Create competitions and assign questions
        foreach ($competitions as $config) {
            // Create the competition
            $competition = Competition::create([
                'name' => $config['name'],
                'description' => $config['description'],
                'entry_fee' => $config['entry_fee'],
                'open_time' => now()->addSeconds(30),
                'start_time' => now()->addMinutes(1),
                'end_time' => now()->addMinutes(61),
                'max_users' => $config['max_users'],
                'game_type' => $config['game_type'],
            ]);

            // Create prize tiers for the competition
            foreach ($config['prize_tiers'] as $tier) {
                PrizeTier::create([
                    'competition_id' => $competition->id,
                    'rank_from' => $tier['rank_from'],
                    'rank_to' => $tier['rank_to'],
                    'prize_type' => $tier['prize_type'],
                    'prize_value' => $tier['prize_value'],
                ]);
            }

            // Assign random questions to the competition
            $competitionQuestions = $questions->random($config['question_count']);
            foreach ($competitionQuestions as $question) {
                CompetitionQuestion::create([
                    'competition_id' => $competition->id,
                    'question_id' => $question->id,
                ]);
            }

            $this->command->info("Created competition '{$competition->name}' with {$competitionQuestions->count()} questions");
        }

        $this->command->info('All competitions and questions have been created successfully!');
    }

    /**
     * Create questions with both English and Kurdish data
     */
    private function createQuestions(): void
    {
        $questions = [
            [
                'question_text' => 'What is the capital of France?',
                'question_text_kurdish' => 'پایتەختی فەڕەنسا چییە؟',
                'options' => ['Paris', 'London', 'Berlin', 'Madrid'],
                'options_kurdish' => ['پاریس', 'لەندەن', 'بەرلین', 'مەدرید'],
                'correct_answer' => 'Paris',
                'correct_answer_kurdish' => 'پاریس',
                'level' => 'easy',
            ],
            [
                'question_text' => 'Who wrote the play "Romeo and Juliet"?',
                'question_text_kurdish' => 'کێ شانۆنامەی "ڕۆمێۆ و جولیەت"ی نووسیوە؟',
                'options' => ['William Shakespeare', 'Charles Dickens', 'Jane Austen', 'Mark Twain'],
                'options_kurdish' => ['ویلیام شەیکسپیر', 'چارڵز دیکنز', 'جەین ئۆستن', 'مارک توەین'],
                'correct_answer' => 'William Shakespeare',
                'correct_answer_kurdish' => 'ویلیام شەیکسپیر',
                'level' => 'easy',
            ],
            [
                'question_text' => 'What is the largest planet in our Solar System?',
                'question_text_kurdish' => 'گەورەترین هەسارە لە کۆمەڵەی خۆردا چییە؟',
                'options' => ['Earth', 'Jupiter', 'Saturn', 'Mars'],
                'options_kurdish' => ['زەوی', 'مشتەری', 'کیوان', 'بەهەشت'],
                'correct_answer' => 'Jupiter',
                'correct_answer_kurdish' => 'مشتەری',
                'level' => 'easy',
            ],
            [
                'question_text' => 'In which year did World War II end?',
                'question_text_kurdish' => 'جەنگی جیھانی دووەم لە چ ساڵێکدا کۆتایی هات؟',
                'options' => ['1945', '1939', '1918', '1965'],
                'options_kurdish' => ['١٩٤٥', '١٩٣٩', '١٩١٨', '١٩٦٥'],
                'correct_answer' => '1945',
                'correct_answer_kurdish' => '١٩٤٥',
                'level' => 'medium',
            ],
            [
                'question_text' => 'What is the chemical symbol for gold?',
                'question_text_kurdish' => 'نیشانەی کیمیایی زێڕ چییە؟',
                'options' => ['Au', 'Ag', 'Gd', 'Go'],
                'options_kurdish' => ['ئۆ', 'ئەگ', 'گەد', 'گۆ'],
                'correct_answer' => 'Au',
                'correct_answer_kurdish' => 'ئۆ',
                'level' => 'easy',
            ],
            [
                'question_text' => 'Who painted the Mona Lisa?',
                'question_text_kurdish' => 'کێ وێنەی مۆنا لیزای کێشاوە؟',
                'options' => ['Leonardo da Vinci', 'Pablo Picasso', 'Vincent van Gogh', 'Claude Monet'],
                'options_kurdish' => ['لیۆناردۆ دا ڤینچی', 'پابڵۆ پیکاسۆ', 'ڤینسێنت ڤان گۆگ', 'کڵۆد مۆنێت'],
                'correct_answer' => 'Leonardo da Vinci',
                'correct_answer_kurdish' => 'لیۆناردۆ دا ڤینچی',
                'level' => 'easy',
            ],
            [
                'question_text' => 'Which language is the most spoken worldwide?',
                'question_text_kurdish' => 'کام زمان لە جیھاندا زۆرترین قسەی پێدەکرێت؟',
                'options' => ['English', 'Mandarin Chinese', 'Spanish', 'Hindi'],
                'options_kurdish' => ['ئینگلیزی', 'چینی مەندەرین', 'ئیسپانی', 'هیندی'],
                'correct_answer' => 'Mandarin Chinese',
                'correct_answer_kurdish' => 'چینی مەندەرین',
                'level' => 'medium',
            ],
            [
                'question_text' => 'What is the smallest prime number?',
                'question_text_kurdish' => 'بچووکترین ژمارەی سەرەتایی چییە؟',
                'options' => ['1', '2', '3', '5'],
                'options_kurdish' => ['١', '٢', '٣', '٥'],
                'correct_answer' => '2',
                'correct_answer_kurdish' => '٢',
                'level' => 'easy',
            ],
            [
                'question_text' => 'Which continent is the Sahara Desert located on?',
                'question_text_kurdish' => 'کەناری سەحەرا لە کام کیشوەردایە؟',
                'options' => ['Africa', 'Asia', 'Australia', 'South America'],
                'options_kurdish' => ['ئەفریقیا', 'ئاسیا', 'ئوسترالیا', 'ئەمریکای باشوور'],
                'correct_answer' => 'Africa',
                'correct_answer_kurdish' => 'ئەفریقیا',
                'level' => 'easy',
            ],
            [
                'question_text' => 'Who is known as the father of computers?',
                'question_text_kurdish' => 'کێ بە باوکی کۆمپیوتەرەکان ناسراوە؟',
                'options' => ['Charles Babbage', 'Alan Turing', 'Bill Gates', 'Steve Jobs'],
                'options_kurdish' => ['چارڵز بابیج', 'ئەلان تیورینگ', 'بیڵ گەیتس', 'ستیڤ جۆبس'],
                'correct_answer' => 'Charles Babbage',
                'correct_answer_kurdish' => 'چارڵز بابیج',
                'level' => 'medium',
            ],
            [
                'question_text' => 'What is the hardest natural substance on Earth?',
                'question_text_kurdish' => 'تەختترین ماددەی سروشتی لە زەویدا چییە؟',
                'options' => ['Diamond', 'Gold', 'Iron', 'Quartz'],
                'options_kurdish' => ['ئەڵماس', 'زێڕ', 'ئاسن', 'کوارتز'],
                'correct_answer' => 'Diamond',
                'correct_answer_kurdish' => 'ئەڵماس',
                'level' => 'easy',
            ],
            [
                'question_text' => 'Which planet is known as the Red Planet?',
                'question_text_kurdish' => 'کام هەسارە بە هەسارەی سوور ناسراوە؟',
                'options' => ['Mars', 'Venus', 'Jupiter', 'Mercury'],
                'options_kurdish' => ['بەهەشت', 'زەهرە', 'مشتەری', 'عەتارد'],
                'correct_answer' => 'Mars',
                'correct_answer_kurdish' => 'بەهەشت',
                'level' => 'easy',
            ],
            [
                'question_text' => 'What is the main ingredient in sushi?',
                'question_text_kurdish' => 'ماددەی سەرەکی لە سوشی چییە؟',
                'options' => ['Rice', 'Fish', 'Seaweed', 'Soy Sauce'],
                'options_kurdish' => ['برنج', 'ماسی', 'گەوەزی دەریا', 'سۆسەی سۆیا'],
                'correct_answer' => 'Rice',
                'correct_answer_kurdish' => 'برنج',
                'level' => 'easy',
            ],
            [
                'question_text' => 'Who discovered penicillin?',
                'question_text_kurdish' => 'کێ پێنیسیلینی دۆزییەوە؟',
                'options' => ['Alexander Fleming', 'Marie Curie', 'Isaac Newton', 'Albert Einstein'],
                'options_kurdish' => ['ئەلێکساندەر فڵێمینگ', 'ماری کوری', 'ئایزاک نیوتن', 'ئەڵبێرت ئەینشتاین'],
                'correct_answer' => 'Alexander Fleming',
                'correct_answer_kurdish' => 'ئەلێکساندەر فڵێمینگ',
                'level' => 'medium',
            ],
            [
                'question_text' => 'Which country gifted the Statue of Liberty to the USA?',
                'question_text_kurdish' => 'کام وڵات پەیکەری ئازادی بەخشی بە ئەمریکا؟',
                'options' => ['France', 'England', 'Germany', 'Italy'],
                'options_kurdish' => ['فەڕەنسا', 'ئینگلتەرا', 'ئەڵمانیا', 'ئیتاڵیا'],
                'correct_answer' => 'France',
                'correct_answer_kurdish' => 'فەڕەنسا',
                'level' => 'medium',
            ],
            [
                'question_text' => 'What is the boiling point of water at sea level in Celsius?',
                'question_text_kurdish' => 'خاڵی کوڵانی ئاو لە ئاستی دەریا لە سیلیسیەس چەندە؟',
                'options' => ['100', '90', '80', '120'],
                'options_kurdish' => ['١٠٠', '٩٠', '٨٠', '١٢٠'],
                'correct_answer' => '100',
                'correct_answer_kurdish' => '١٠٠',
                'level' => 'easy',
            ],
            [
                'question_text' => 'Which organ in the human body is responsible for pumping blood?',
                'question_text_kurdish' => 'کام ئەندام لە لەشی مرۆڤدا بەرپرسیارە لە پەمپکردنی خوێن؟',
                'options' => ['Heart', 'Liver', 'Lungs', 'Kidney'],
                'options_kurdish' => ['دڵ', 'جگەر', 'سییەکان', 'گورچیلە'],
                'correct_answer' => 'Heart',
                'correct_answer_kurdish' => 'دڵ',
                'level' => 'easy',
            ],
            [
                'question_text' => 'What is the largest mammal in the world?',
                'question_text_kurdish' => 'گەورەترین شیردەر لە جیھاندا چییە؟',
                'options' => ['Blue Whale', 'Elephant', 'Giraffe', 'Hippopotamus'],
                'options_kurdish' => ['نەهەنگی شین', 'فیل', 'زڕافە', 'هیپۆپۆتامەس'],
                'correct_answer' => 'Blue Whale',
                'correct_answer_kurdish' => 'نەهەنگی شین',
                'level' => 'medium',
            ],
            [
                'question_text' => 'Who developed the theory of relativity?',
                'question_text_kurdish' => 'کێ تیۆری ڕێژەیی پەرەپێدا؟',
                'options' => ['Albert Einstein', 'Isaac Newton', 'Galileo Galilei', 'Nikola Tesla'],
                'options_kurdish' => ['ئەڵبێرت ئەینشتاین', 'ئایزاک نیوتن', 'گالیلیۆ گالیلی', 'نیکۆلا تێسلا'],
                'correct_answer' => 'Albert Einstein',
                'correct_answer_kurdish' => 'ئەڵبێرت ئەینشتاین',
                'level' => 'medium',
            ],
            [
                'question_text' => 'Which gas do plants absorb from the atmosphere?',
                'question_text_kurdish' => 'کام گاز ڕووەکەکان لە بەرگەهەوا وەردەگرن؟',
                'options' => ['Carbon Dioxide', 'Oxygen', 'Nitrogen', 'Hydrogen'],
                'options_kurdish' => ['دوو ئۆکسیدی کاربۆن', 'ئۆکسجین', 'نایترۆجین', 'هایدرۆجین'],
                'correct_answer' => 'Carbon Dioxide',
                'correct_answer_kurdish' => 'دوو ئۆکسیدی کاربۆن',
                'level' => 'easy',
            ],
            [
                'question_text' => 'What is the capital of Japan?',
                'question_text_kurdish' => 'پایتەختی ژاپۆن چییە؟',
                'options' => ['Tokyo', 'Kyoto', 'Osaka', 'Yokohama'],
                'options_kurdish' => ['تۆکیۆ', 'کیۆتۆ', 'ئۆساکا', 'یۆکۆهاما'],
                'correct_answer' => 'Tokyo',
                'correct_answer_kurdish' => 'تۆکیۆ',
                'level' => 'easy',
            ],
            [
                'question_text' => 'Which element has the chemical symbol O?',
                'question_text_kurdish' => 'کام توخم نیشانەی کیمیایی O ی هەیە؟',
                'options' => ['Oxygen', 'Osmium', 'Oganesson', 'Osmium'],
                'options_kurdish' => ['ئۆکسجین', 'ئۆسمیەم', 'ئۆگانێسۆن', 'ئۆسمیەم'],
                'correct_answer' => 'Oxygen',
                'correct_answer_kurdish' => 'ئۆکسجین',
                'level' => 'easy',
            ],
            [
                'question_text' => 'Who wrote "Pride and Prejudice"?',
                'question_text_kurdish' => 'کێ "شەرەف و پێشداوری"ی نووسیوە؟',
                'options' => ['Jane Austen', 'Charlotte Brontë', 'Emily Brontë', 'Mary Shelley'],
                'options_kurdish' => ['جەین ئۆستن', 'شارلۆت برۆنتێ', 'ئێمیلی برۆنتێ', 'ماری شێڵی'],
                'correct_answer' => 'Jane Austen',
                'correct_answer_kurdish' => 'جەین ئۆستن',
                'level' => 'medium',
            ],
            [
                'question_text' => 'What is the largest ocean on Earth?',
                'question_text_kurdish' => 'گەورەترین ئۆقیانوس لە زەویدا چییە؟',
                'options' => ['Pacific Ocean', 'Atlantic Ocean', 'Indian Ocean', 'Arctic Ocean'],
                'options_kurdish' => ['ئۆقیانوسی هێمن', 'ئۆقیانوسی ئەتڵەسی', 'ئۆقیانوسی هیندی', 'ئۆقیانوسی ئەرکتیک'],
                'correct_answer' => 'Pacific Ocean',
                'correct_answer_kurdish' => 'ئۆقیانوسی هێمن',
                'level' => 'easy',
            ],
            [
                'question_text' => 'Which country is home to the kangaroo?',
                'question_text_kurdish' => 'کام وڵات نیشتەجێی کەنگەرۆیە؟',
                'options' => ['Australia', 'New Zealand', 'South Africa', 'India'],
                'options_kurdish' => ['ئوسترالیا', 'نیوزیلاند', 'ئەفریقیای باشوور', 'هیندستان'],
                'correct_answer' => 'Australia',
                'correct_answer_kurdish' => 'ئوسترالیا',
                'level' => 'easy',
            ],
            [
                'question_text' => 'What is the main component of the sun?',
                'question_text_kurdish' => 'پێکھاتەی سەرەکی خۆر چییە؟',
                'options' => ['Liquid Lava', 'Molten Iron', 'Hot Gases', 'Solid Rock'],
                'options_kurdish' => ['لاڤای شل', 'ئاسنی کوڵاو', 'گازی گەرم', 'بەردی ڕەق'],
                'correct_answer' => 'Hot Gases',
                'correct_answer_kurdish' => 'گازی گەرم',
                'level' => 'medium',
            ],
            [
                'question_text' => 'Who invented the telephone?',
                'question_text_kurdish' => 'کێ تەلەفۆنی داھێنا؟',
                'options' => ['Thomas Edison', 'Alexander Graham Bell', 'Nikola Tesla', 'Guglielmo Marconi'],
                'options_kurdish' => ['تۆماس ئێدیسۆن', 'ئەلێکساندەر گراهام بێڵ', 'نیکۆلا تێسلا', 'گوجێڵمۆ مارکۆنی'],
                'correct_answer' => 'Alexander Graham Bell',
                'correct_answer_kurdish' => 'ئەلێکساندەر گراهام بێڵ',
                'level' => 'medium',
            ],
            [
                'question_text' => 'What is the smallest country in the world?',
                'question_text_kurdish' => 'بچووکترین وڵات لە جیھاندا چییە؟',
                'options' => ['Monaco', 'San Marino', 'Vatican City', 'Liechtenstein'],
                'options_kurdish' => ['مۆناکۆ', 'سان مارینۆ', 'شاری ڤاتیکان', 'لیختنشتاین'],
                'correct_answer' => 'Vatican City',
                'correct_answer_kurdish' => 'شاری ڤاتیکان',
                'level' => 'medium',
            ],
            [
                'question_text' => 'Which animal is known as the King of the Jungle?',
                'question_text_kurdish' => 'کام ئاژەڵ بە پاشای دارستان ناسراوە؟',
                'options' => ['Lion', 'Tiger', 'Elephant', 'Gorilla'],
                'options_kurdish' => ['شێر', 'پڵنگ', 'فیل', 'گۆریلا'],
                'correct_answer' => 'Lion',
                'correct_answer_kurdish' => 'شێر',
                'level' => 'easy',
            ],
            [
                'question_text' => 'What is the currency of the United Kingdom?',
                'question_text_kurdish' => 'دراوی شانشینی یەکگرتوو چییە؟',
                'options' => ['Euro', 'Dollar', 'Pound Sterling', 'Yen'],
                'options_kurdish' => ['یۆرۆ', 'دۆلار', 'پاوەندی ستەرلینگ', 'یەن'],
                'correct_answer' => 'Pound Sterling',
                'correct_answer_kurdish' => 'پاوەندی ستەرلینگ',
                'level' => 'easy',
            ],
        ];

        foreach ($questions as $question) {
            Question::create([
                'question_text' => $question['question_text'],
                'question_text_kurdish' => $question['question_text_kurdish'],
                'question_type' => 'multi_choice',
                'options' => $question['options'],
                'options_kurdish' => $question['options_kurdish'],
                'correct_answer' => $question['correct_answer'],
                'correct_answer_kurdish' => $question['correct_answer_kurdish'],
                'level' => $question['level'],
            ]);
        }

        $this->command->info('Created ' . count($questions) . ' questions with bilingual support');
    }
}