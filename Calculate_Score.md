

🎯  Calculate Score 

Step 1:
✅ Player answers questions during the competition → answers saved in user_answers.

Each record stores:
	•	player_id
	•	competition_id
	•	question_id
	•	user_answer
	•	is_correct (true/false)

⸻

Step 2:
✅ After the session ends → Fetch the player’s correct answers.

SELECT COUNT(*) 
FROM user_answers 
WHERE player_id = :playerId 
AND competition_id = :competitionId 
AND is_correct = TRUE;

This count gives you the base score (e.g., 7 correct answers = score 7).

⸻

Step 3 (Optional Advanced Rules):
You can add bonus points if you want based on:

Bonus	Example
Fast answer time	e.g., +1 point for answering within 5 seconds
Streak bonus	e.g., +2 points for 3 correct answers in a row
First correct answer bonus	e.g., +5 points if first to answer

(but your current database does not yet track time per answer — only correctness).

⸻

✅ Basic Score Formula

Without advanced bonus:

Player Score = Number of Correct Answers

Simple, fast, and perfect for starting.

⸻

🔥 Example

Imagine the competition has 10 questions:

Question	Player’s Answer	Correct?
Q1	Correct	✅
Q2	Incorrect	❌
Q3	Correct	✅
Q4	Correct	✅
Q5	Incorrect	❌
Q6	Correct	✅
Q7	Correct	✅
Q8	Incorrect	❌
Q9	Correct	✅
Q10	Correct	✅

Result:
	•	Correct Answers = 7
	•	Final Score = 7

Then you insert into session_leaderboard:

INSERT INTO session_leaderboard (player_id, competition_id, score, rank)
VALUES (:playerId, :competitionId, 7, NULL);

Later, you assign the rank after sorting all players by their score (highest first).

⸻

📊 Final Ranking
	•	Fetch all players in the competition.
	•	Sort by score DESC.
	•	Assign rank from 1 down.

Example:

SELECT player_id, score 
FROM session_leaderboard 
WHERE competition_id = :competitionId
ORDER BY score DESC;

Then assign:
	•	1st place to highest score
	•	2nd place to second highest, etc.

⸻

🧠 Very Simple Flow

Step	Action
1	Player answers stored in user_answers
2	Count correct answers = score
3	Save score into session_leaderboard
4	Rank players
5	Assign prizes using prize_tiers



⸻

🚀 Bonus Idea for Later:

If you later add answered_at timestamp per question,
✅ you can reward faster players too (e.g., speed-based bonus)!

⸻

Would you also like me to give you ready SQL code for:
	•	Calculating the scores for all players automatically
	•	Inserting their leaderboard entries?
