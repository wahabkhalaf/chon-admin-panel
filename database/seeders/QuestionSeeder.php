<?php

namespace Database\Seeders;

use App\Models\Question;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuestionSeeder extends Seeder
{
    /**
     * Run the database seed.
     */
    public function run(): void
    {
        // Create 30 multi_choice questions using factory with proper options
        for ($i = 0; $i < 30; $i++) {
            Question::create($this->createMultiChoiceQuestion());
        }

        // Create some specific questions with proper options handling
        Question::create([
            'question_text' => 'What is the capital of France?',
            'question_text_kurdish' => 'پایتەختی فەڕەنسا چییە؟',
            'question_type' => 'multi_choice',
            'options' => [
                ['option' => 'London', 'is_correct' => false],
                ['option' => 'Paris', 'is_correct' => true],
                ['option' => 'Berlin', 'is_correct' => false],
                ['option' => 'Madrid', 'is_correct' => false],
            ],
            'options_kurdish' => [
                ['option' => 'لەندەن', 'is_correct' => false],
                ['option' => 'پاریس', 'is_correct' => true],
                ['option' => 'بەرلین', 'is_correct' => false],
                ['option' => 'مەدرید', 'is_correct' => false],
            ],
            'correct_answer' => 'Paris',
            'correct_answer_kurdish' => 'پاریس',
            'level' => 'easy',
        ]);

        $questions = [
            [
                'question_text' => 'What is the capital city of France?',
                'question_text_kurdish' => 'پایتەختی فەڕەنسا چییە؟',
                'question_type' => 'multi_choice',
                'options' => ['Paris', 'London', 'Berlin', 'Madrid'],
                'options_kurdish' => ['پاریس', 'لەندەن', 'بەرلین', 'مەدرید'],
                'correct_answer' => 'Paris',
                'correct_answer_kurdish' => 'پاریس',
                'level' => 'easy',
            ],
            [
                'question_text' => 'Who wrote the play "Romeo and Juliet"?',
                'question_text_kurdish' => 'کێ شانۆنامەی "ڕۆمێۆ و جولیەت"ی نووسیوە؟',
                'question_type' => 'multi_choice',
                'options' => ['William Shakespeare', 'Charles Dickens', 'Jane Austen', 'Mark Twain'],
                'options_kurdish' => ['ویلیام شەیکسپیر', 'چارڵز دیکنز', 'جەین ئۆستن', 'مارک توەین'],
                'correct_answer' => 'William Shakespeare',
                'correct_answer_kurdish' => 'ویلیام شەیکسپیر',
                'level' => 'easy',
            ],
            [
                'question_text' => 'What is the largest planet in our Solar System?',
                'question_text_kurdish' => 'گەورەترین هەسارە لە کۆمەڵەی خۆردا چییە؟',
                'question_type' => 'multi_choice',
                'options' => ['Earth', 'Jupiter', 'Saturn', 'Mars'],
                'options_kurdish' => ['زەوی', 'مشتەری', 'کیوان', 'بەهەشت'],
                'correct_answer' => 'Jupiter',
                'correct_answer_kurdish' => 'مشتەری',
                'level' => 'easy',
            ],
            [
                'question_text' => 'In which year did World War II end?',
                'question_text_kurdish' => 'جەنگی جیھانی دووەم لە چ ساڵێکدا کۆتایی هات؟',
                'question_type' => 'multi_choice',
                'options' => ['1945', '1939', '1918', '1965'],
                'options_kurdish' => ['١٩٤٥', '١٩٣٩', '١٩١٨', '١٩٦٥'],
                'correct_answer' => '1945',
                'correct_answer_kurdish' => '١٩٤٥',
                'level' => 'medium',
            ],
            [
                'question_text' => 'What is the chemical symbol for gold?',
                'question_text_kurdish' => 'نیشانەی کیمیایی زێڕ چییە؟',
                'question_type' => 'multi_choice',
                'options' => ['Au', 'Ag', 'Gd', 'Go'],
                'options_kurdish' => ['ئۆ', 'ئەگ', 'گەد', 'گۆ'],
                'correct_answer' => 'Au',
                'correct_answer_kurdish' => 'ئۆ',
                'level' => 'easy',
            ],
            [
                'question_text' => 'Who painted the Mona Lisa?',
                'question_text_kurdish' => 'کێ وێنەی مۆنا لیزای کێشاوە؟',
                'question_type' => 'multi_choice',
                'options' => ['Leonardo da Vinci', 'Pablo Picasso', 'Vincent van Gogh', 'Claude Monet'],
                'options_kurdish' => ['لیۆناردۆ دا ڤینچی', 'پابڵۆ پیکاسۆ', 'ڤینسێنت ڤان گۆگ', 'کڵۆد مۆنێت'],
                'correct_answer' => 'Leonardo da Vinci',
                'correct_answer_kurdish' => 'لیۆناردۆ دا ڤینچی',
                'level' => 'easy',
            ],
            [
                'question_text' => 'Which language is the most spoken worldwide?',
                'question_text_kurdish' => 'کام زمان لە جیھاندا زۆرترین قسەی پێدەکرێت؟',
                'question_type' => 'multi_choice',
                'options' => ['English', 'Mandarin Chinese', 'Spanish', 'Hindi'],
                'options_kurdish' => ['ئینگلیزی', 'چینی مەندەرین', 'ئیسپانی', 'هیندی'],
                'correct_answer' => 'Mandarin Chinese',
                'correct_answer_kurdish' => 'چینی مەندەرین',
                'level' => 'medium',
            ],
            [
                'question_text' => 'What is the smallest prime number?',
                'question_text_kurdish' => 'بچووکترین ژمارەی سەرەتایی چییە؟',
                'question_type' => 'multi_choice',
                'options' => ['1', '2', '3', '5'],
                'options_kurdish' => ['١', '٢', '٣', '٥'],
                'correct_answer' => '2',
                'correct_answer_kurdish' => '٢',
                'level' => 'easy',
            ],
            [
                'question_text' => 'Which continent is the Sahara Desert located on?',
                'question_text_kurdish' => 'کەناری سەحەرا لە کام کیشوەردایە؟',
                'question_type' => 'multi_choice',
                'options' => ['Africa', 'Asia', 'Australia', 'South America'],
                'options_kurdish' => ['ئەفریقیا', 'ئاسیا', 'ئوسترالیا', 'ئەمریکای باشوور'],
                'correct_answer' => 'Africa',
                'correct_answer_kurdish' => 'ئەفریقیا',
                'level' => 'easy',
            ],
            [
                'question_text' => 'Who is known as the father of computers?',
                'question_text_kurdish' => 'کێ بە باوکی کۆمپیوتەرەکان ناسراوە؟',
                'question_type' => 'multi_choice',
                'options' => ['Charles Babbage', 'Alan Turing', 'Bill Gates', 'Steve Jobs'],
                'options_kurdish' => ['چارڵز بابیج', 'ئەلان تیورینگ', 'بیڵ گەیتس', 'ستیڤ جۆبس'],
                'correct_answer' => 'Charles Babbage',
                'correct_answer_kurdish' => 'چارڵز بابیج',
                'level' => 'medium',
            ],
            [
                'question_text' => 'What is the hardest natural substance on Earth?',
                'question_text_kurdish' => 'تەختترین ماددەی سروشتی لە زەویدا چییە؟',
                'question_type' => 'multi_choice',
                'options' => ['Diamond', 'Gold', 'Iron', 'Quartz'],
                'options_kurdish' => ['ئەڵماس', 'زێڕ', 'ئاسن', 'کوارتز'],
                'correct_answer' => 'Diamond',
                'correct_answer_kurdish' => 'ئەڵماس',
                'level' => 'easy',
            ],
            [
                'question_text' => 'Which planet is known as the Red Planet?',
                'question_text_kurdish' => 'کام هەسارە بە هەسارەی سوور ناسراوە؟',
                'question_type' => 'multi_choice',
                'options' => ['Mars', 'Venus', 'Jupiter', 'Mercury'],
                'options_kurdish' => ['بەهەشت', 'زەهرە', 'مشتەری', 'عەتارد'],
                'correct_answer' => 'Mars',
                'correct_answer_kurdish' => 'بەهەشت',
                'level' => 'easy',
            ],
            [
                'question_text' => 'What is the main ingredient in sushi?',
                'question_text_kurdish' => 'ماددەی سەرەکی لە سوشی چییە؟',
                'question_type' => 'multi_choice',
                'options' => ['Rice', 'Fish', 'Seaweed', 'Soy Sauce'],
                'options_kurdish' => ['برنج', 'ماسی', 'گەوەزی دەریا', 'سۆسەی سۆیا'],
                'correct_answer' => 'Rice',
                'correct_answer_kurdish' => 'برنج',
                'level' => 'easy',
            ],
            [
                'question_text' => 'Who discovered penicillin?',
                'question_text_kurdish' => 'کێ پێنیسیلینی دۆزییەوە؟',
                'question_type' => 'multi_choice',
                'options' => ['Alexander Fleming', 'Marie Curie', 'Isaac Newton', 'Albert Einstein'],
                'options_kurdish' => ['ئەلێکساندەر فڵێمینگ', 'ماری کوری', 'ئایزاک نیوتن', 'ئەڵبێرت ئەینشتاین'],
                'correct_answer' => 'Alexander Fleming',
                'correct_answer_kurdish' => 'ئەلێکساندەر فڵێمینگ',
                'level' => 'medium',
            ],
            [
                'question_text' => 'Which country gifted the Statue of Liberty to the USA?',
                'question_text_kurdish' => 'کام وڵات پەیکەری ئازادی بەخشی بە ئەمریکا؟',
                'question_type' => 'multi_choice',
                'options' => ['France', 'England', 'Germany', 'Italy'],
                'options_kurdish' => ['فەڕەنسا', 'ئینگلتەرا', 'ئەڵمانیا', 'ئیتاڵیا'],
                'correct_answer' => 'France',
                'correct_answer_kurdish' => 'فەڕەنسا',
                'level' => 'medium',
            ],
            [
                'question_text' => 'What is the boiling point of water at sea level in Celsius?',
                'question_text_kurdish' => 'خاڵی کوڵانی ئاو لە ئاستی دەریا لە سیلیسیەس چەندە؟',
                'question_type' => 'multi_choice',
                'options' => ['100', '90', '80', '120'],
                'options_kurdish' => ['١٠٠', '٩٠', '٨٠', '١٢٠'],
                'correct_answer' => '100',
                'correct_answer_kurdish' => '١٠٠',
                'level' => 'easy',
            ],
            [
                'question_text' => 'Which organ in the human body is responsible for pumping blood?',
                'question_text_kurdish' => 'کام ئەندام لە لەشی مرۆڤدا بەرپرسیارە لە پەمپکردنی خوێن؟',
                'question_type' => 'multi_choice',
                'options' => ['Heart', 'Liver', 'Lungs', 'Kidney'],
                'options_kurdish' => ['دڵ', 'جگەر', 'سییەکان', 'گورچیلە'],
                'correct_answer' => 'Heart',
                'correct_answer_kurdish' => 'دڵ',
                'level' => 'easy',
            ],
            [
                'question_text' => 'What is the largest mammal in the world?',
                'question_text_kurdish' => 'گەورەترین شیردەر لە جیھاندا چییە؟',
                'question_type' => 'multi_choice',
                'options' => ['Blue Whale', 'Elephant', 'Giraffe', 'Hippopotamus'],
                'options_kurdish' => ['نەهەنگی شین', 'فیل', 'زڕافە', 'هیپۆپۆتامەس'],
                'correct_answer' => 'Blue Whale',
                'correct_answer_kurdish' => 'نەهەنگی شین',
                'level' => 'medium',
            ],
            [
                'question_text' => 'Who developed the theory of relativity?',
                'question_text_kurdish' => 'کێ تیۆری ڕێژەیی پەرەپێدا؟',
                'question_type' => 'multi_choice',
                'options' => ['Albert Einstein', 'Isaac Newton', 'Galileo Galilei', 'Nikola Tesla'],
                'options_kurdish' => ['ئەڵبێرت ئەینشتاین', 'ئایزاک نیوتن', 'گالیلیۆ گالیلی', 'نیکۆلا تێسلا'],
                'correct_answer' => 'Albert Einstein',
                'correct_answer_kurdish' => 'ئەڵبێرت ئەینشتاین',
                'level' => 'medium',
            ],
            [
                'question_text' => 'Which gas do plants absorb from the atmosphere?',
                'question_text_kurdish' => 'کام گاز ڕووەکەکان لە بەرگەهەوا وەردەگرن؟',
                'question_type' => 'multi_choice',
                'options' => ['Carbon Dioxide', 'Oxygen', 'Nitrogen', 'Hydrogen'],
                'options_kurdish' => ['دوو ئۆکسیدی کاربۆن', 'ئۆکسجین', 'نایترۆجین', 'هایدرۆجین'],
                'correct_answer' => 'Carbon Dioxide',
                'correct_answer_kurdish' => 'دوو ئۆکسیدی کاربۆن',
                'level' => 'easy',
            ],
        ];

        foreach ($questions as $question) {
            DB::table('questions')->insert([
                'question_text' => $question['question_text'],
                'question_text_kurdish' => $question['question_text_kurdish'],
                'question_type' => $question['question_type'],
                'options' => json_encode($question['options']),
                'options_kurdish' => json_encode($question['options_kurdish']),
                'correct_answer' => $question['correct_answer'],
                'correct_answer_kurdish' => $question['correct_answer_kurdish'],
                'level' => $question['level'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Create a multi-choice question with proper options in both English and Kurdish
     */
    private function createMultiChoiceQuestion(): array
    {
        $questions = [
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

        $question = $questions[array_rand($questions)];

        return [
            'question_text' => $question['question_text'],
            'question_text_kurdish' => $question['question_text_kurdish'],
            'question_type' => 'multi_choice',
            'options' => $question['options'],
            'options_kurdish' => $question['options_kurdish'],
            'correct_answer' => $question['correct_answer'],
            'correct_answer_kurdish' => $question['correct_answer_kurdish'],
            'level' => $question['level'],
        ];
    }
}
