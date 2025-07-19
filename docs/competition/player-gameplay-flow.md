# Player Gameplay Flow: A Step-by-Step Guide

This document provides a complete step-by-step guide to the player's journey through a competition, from discovery to completion. It details the sequence of REST API calls and WebSocket events that the mobile client should handle.

## Overview of the Flow

The player's journey can be broken down into four distinct phases:

1.  **Discovery & Entry**: The player finds a competition and joins it (including payment). This phase uses a mix of REST APIs for stateless actions and WebSockets for real-time confirmation.
2.  **Waiting Room**: After joining, the player waits for the competition to begin. This phase is managed over a persistent WebSocket connection.
3.  **Live Gameplay**: The competition is active. Questions are sent and answers are submitted in real-time over WebSockets.
4.  **Results & Leaderboard**: The competition ends, scores are calculated, and results are displayed.

---

### Phase 1: Discovery & Entry (REST API & WebSocket)

In this phase, the player browses competitions and officially enters one.

1.  **Fetch Active Competitions (REST)**

    -   The app makes a request to get a list of all competitions the player can join. This data should be cached to improve performance.
    -   **Endpoint**: `GET /api/v1/competitions`
    -   **Client Action**: Display the list of competitions in the UI.

2.  **Initiate Join & Pay (REST)**

    -   The player taps to join a competition with an entry fee.
    -   **Endpoint**: `POST /api/v1/competitions/{id}/join`
    -   **Client Action**: The app initiates the join process, which leads to a payment flow handled by a payment gateway. For a detailed breakdown of this transactional flow, see `joining-a-competition.md`.

3.  **Establish WebSocket Connection**

    -   While the above is happening, the app should establish a persistent WebSocket connection for real-time updates. The connection must be authenticated using the player's token.
    -   **Endpoint**: `wss://api.yourdomain.com/game?token=JWT_TOKEN`

4.  **Receive Join Confirmation (WebSocket)**
    -   After the payment is successfully processed on the backend, the server sends a confirmation event.
    -   **Event (Server → Client)**: `join_successful`
    -   **Payload**: `{ "competitionId": "...", "message": "..." }`
    -   **Client Action**: Transition the UI to the "Waiting Room" screen for the specific competition.

---

### Phase 2: The Waiting Room (WebSocket)

The player has successfully joined and is now waiting for the game to start.

-   **Client Action**: The app displays a waiting screen, perhaps showing a countdown to the start time or a list of other players who have joined.
-   **Server Event**: The server may periodically send `ping` or `countdown_update` events to keep the connection alive and the user informed.

---

### Phase 3: Live Gameplay (WebSocket)

This is the core, real-time loop of the competition. All events are sent over the active WebSocket connection.

1.  **Competition Starts**

    -   A `competition_started` event can be sent to signal the UI to change from the waiting room to the main game view.
    -   **Event (Server → Client)**: `competition_started`

2.  **A New Question Arrives**

    -   The server broadcasts the first question to all connected players.
    -   **Event (Server → Client)**: `new_question`
    -   **Payload**: `{ "questionId": "q_abc", "text": "...", "options": ["A", "B", "C"], "timeLimit": 10 }`
    -   **Client Action**: Display the question and options. Start a countdown timer based on `timeLimit`.

3.  **Player Submits an Answer**

    -   The player selects an answer before the timer runs out.
    -   **Event (Client → Server)**: `submit_answer`
    -   **Payload**: `{ "competitionId": "...", "questionId": "q_abc", "answer": "A" }`

4.  **Receive Instant Feedback**
    -   The server immediately validates the answer and sends a result back to the player.
    -   **Event (Server → Client)**: `answer_result`
    -   **Payload**: `{ "questionId": "q_abc", "isCorrect": true, "yourScoreSoFar": 5 }`
    -   **Client Action**: Show feedback (e.g., correct/incorrect animation) and update the player's score on the UI.

This loop (`new_question` -> `submit_answer` -> `answer_result`) repeats for every question in the competition.

---

### Phase 4: Results & Leaderboard (WebSocket & REST API)

The last question has been answered, and the competition is over.

1.  **Competition Ends (WebSocket)**

    -   The server notifies all players that the game has finished.
    -   **Event (Server → Client)**: `competition_finished`
    -   **Payload**: `{ "message": "The competition has ended! Calculating final scores..." }`
    -   **Client Action**: Display a "calculating results" message while the backend processes the final scores and ranks.

2.  **Receive Final Personal Results (WebSocket)**

    -   Once scores are calculated, the server pushes the final, personalized results to each player.
    -   **Event (Server → Client)**: `final_result`
    -   **Payload**: `{ "yourScore": 7, "yourRank": 42, "totalPlayers": 1000, "prizeWon": "..." }`
    -   **Client Action**: Display a summary screen with the player's final score, rank, and any prize they may have won.

3.  **View Full Leaderboard (REST)**
    -   To see how they stacked up against everyone else, the player can navigate to a full leaderboard screen. This data is fetched via a standard REST API to allow for pagination.
    -   **Endpoint**: `GET /api/v1/competitions/{id}/leaderboard?page=1&limit=20`
    -   **Client Action**: Display a scrollable, paginated list of all players, their scores, and their ranks.
