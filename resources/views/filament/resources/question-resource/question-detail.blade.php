<div class="p-4 space-y-4">
    <div class="flex justify-between">
        <h2 class="text-xl font-bold">{{ $question->question_text }}</h2>
        <span
            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
            @if ($question->level === 'easy') bg-green-100 text-green-800 @endif
            @if ($question->level === 'medium') bg-yellow-100 text-yellow-800 @endif
            @if ($question->level === 'hard') bg-red-100 text-red-800 @endif
        ">
            {{ ucfirst($question->level) }}
        </span>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-sm text-gray-500">Question Type</p>
            <p class="font-medium">{{ \App\Models\Question::TYPES[$question->question_type] }}</p>
        </div>

        <div>
            <p class="text-sm text-gray-500">Created</p>
            <p class="font-medium">{{ $question->created_at->format('M d, Y') }}</p>
        </div>
    </div>

    <div>
        <p class="text-sm text-gray-500">Correct Answer</p>
        <p class="font-medium">{{ $question->correct_answer }}</p>
    </div>

    @if ($question->question_type === 'multi_choice' && !empty($question->options))
        <div>
            <p class="text-sm text-gray-500 mb-2">Answer Options</p>
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($question->options as $option)
                    <li class="@if (isset($option['is_correct']) && $option['is_correct']) font-bold text-green-600 @endif">
                        {{ $option['option'] }}
                        @if (isset($option['is_correct']) && $option['is_correct'])
                            <span class="text-xs ml-1">(Correct)</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-4">
        <p class="text-sm text-gray-500 mb-2">Attached to Competitions</p>
        @if ($question->competitions_count > 0)
            <div class="grid grid-cols-1 gap-2">
                @foreach ($question->competitions as $competition)
                    <div class="flex justify-between items-center px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-md">
                        <div>
                            <span class="font-medium">{{ $competition->name }}</span>
                            <span class="ml-2 text-xs text-gray-500">{{ $competition->getStatus() }}</span>
                        </div>
                        <span class="text-xs">
                            {{ $competition->start_time->format('M d, Y') }}
                        </span>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500 italic">Not attached to any competitions</p>
        @endif
    </div>
</div>
