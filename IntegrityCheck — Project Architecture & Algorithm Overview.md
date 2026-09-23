## 1. What This Project Is

IntegrityCheck is a plagiarism/similarity detection system for academic assignment submissions. Teachers create assignments, students submit their written work, and the system automatically compares every new submission against both all previous submissions for that assignment and the open web. If a submission looks too similar to another student's work or a web page, the system flags it for a teacher to review.

The goal is not just to say "these two are similar" — it's to catch two different kinds of copying:

- **Exact or near-exact copying** (someone copy-pasted a sentence or paragraph directly).
- **Paraphrased copying** (someone reworded the original text but kept the same meaning and ideas).

A single method usually can't catch both well, so this system combines two different techniques into one score.

## 2. The Two Algorithms (The "Brain" of the System)

### 2.1 Lexical Similarity — Shingling + Jaccard Similarity (built from scratch)

This method catches **exact or near-exact copying**.

**How it works, step by step:**

1. Clean the text — lowercase everything, remove punctuation, remove common filler words (stopwords).
    
2. Break the cleaned text into overlapping chunks of 5 words at a time, called **shingles**. For example, "the cat sat on the mat" becomes shingles like ["the cat sat on the", "cat sat on the mat"].
    
3. Turn each document into a **set** of these shingles.
    
4. Compare two documents by measuring how much their shingle sets overlap. This is called **Jaccard Similarity**:
    
    ```
    Jaccard Similarity = (shingles shared by both documents)
                         / (total unique shingles across both documents)
    ```
    
5. A high Jaccard score means the two texts share a lot of the exact same 5-word phrases — a strong sign of direct copying.
    

**Why this method alone isn't enough:** if someone rewrites a sentence in their own words, the exact 5-word phrases won't match anymore, even though the meaning is identical. That's what the second method is for.

**A practical wrinkle:** the shingles above are built from *cleaned* words (lowercase, punctuation stripped, stopwords removed) — the stored match is not a literal substring of the original submission. Reconstructing where a match actually falls in the real, punctuated text (to underline it on screen) has to tolerate the punctuation and stopwords the cleaning step threw away, rather than doing an exact string search for the cleaned phrase.

### 2.2 Semantic Similarity — Sentence-BERT Embeddings + Cosine Similarity

This method catches **paraphrased copying** — same meaning, different words.

**How it works, step by step:**

1. Each submission is passed through a pretrained language model called **Sentence-BERT (SBERT)**. This model reads the text and converts it into a list of numbers (a "vector" or "embedding") that represents the _meaning_ of the text, not just the exact words.
2. Two documents that mean similar things end up with similar-looking number vectors, even if the actual wording is completely different.
3. To compare two vectors, we calculate **Cosine Similarity** — a measure of how closely two vectors point in the same direction. This part of the math (the actual similarity calculation) is written from scratch, not taken from a library.
4. A high cosine similarity score means the two submissions talk about the same ideas in a similar way, even if the wording looks different on the surface.

### 2.3 A Third Source: Live Web Matching

Both methods above compare a submission only against *other students' submissions on the same assignment*. That misses the most common real case: a student copying from a website rather than a classmate. To cover that, the same submission is also checked against the open web:

1. The submission's longest, most distinctive sentences are used to build a search query.
2. A handful of top search results are fetched and their page text extracted (navigation, scripts, and boilerplate are stripped out first).
3. A long page is split into passage-sized chunks — comparing a short submission against an entire article would dilute the score, so each chunk is scored independently and the best-matching chunk is kept.
4. Each chunk goes through the *exact same* lexical (shingling + Jaccard) and semantic (SBERT + cosine) scoring used for submission-vs-submission comparisons above — no separate algorithm was written for this case.

A web match is stored and reviewed exactly like a peer match, just labeled with its source URL and title instead of another student's name, and shown to the teacher with the same highlighted-overlap view.

### 2.4 Combining Both Scores

Neither method alone tells the whole story, so the system combines both into one final score, for every comparison — peer or web:

```
Combined Score = (w1 × Lexical Score) + (w2 × Semantic Score)
```

Where `w1 = 0.4` and `w2 = 0.6` — semantic similarity is weighted higher since it's the harder signal to fake by superficial rewording. If the combined score reaches the threshold set by the teacher for that assignment (0.35 by default, adjustable per assignment), the comparison is automatically flagged for review.

Storing both individual scores (not just the combined one) lets the system later show _why_ something was flagged — was it flagged for copying exact phrases, for meaning-based similarity, or both?

## 3. System Architecture (The Moving Parts)

The system is split into two separate services that talk to each other, with a background queue between the Laravel app and the similarity engine so a student's submit click never has to wait on it:

```
┌─────────────────────────┐              ┌──────────────────────────┐
│      Laravel App        │              │  Python FastAPI Service  │
│ (Blade + Tailwind        │              │    (Similarity Engine)   │
│  + Alpine.js)            │              │                          │
│                          │              │ - Preprocessing          │
│ - User accounts           │             │ - Shingling + Jaccard    │
│   (student/teacher/admin) │             │ - SBERT embeddings       │
│ - Faculties/semesters/     │            │ - Cosine similarity      │
│   courses/assignments      │            │ - Combined scoring       │
│ - Submissions                │          │ - Web search + scraping  │──► live web
│ - Similarity reports          │         │   (SerpApi + chunking)   │    (search results
│ - Teacher review dashboard      │       └──────────────────────────┘     + page text)
│ - Analytics dashboard             │                    ▲
│                                     │                   │ HTTP / JSON
│ ┌─────────────────────────┐          │                 │
│ │ Queue worker              │────────┼─────────────────┘
│ │ (CheckSubmissionSimilarity) │       │  dispatched after a submission is saved,
│ └─────────────────────────┘          │  runs the check asynchronously
└─────────────────────────┘
         │
         ▼
┌─────────────────────────┐
│      MySQL Database      │
│ (users, courses,          │
│  assignments, submissions,│
│  similarity_reports)      │
└─────────────────────────┘
```

**Why split it this way:** Laravel is good at handling users, roles, pages, and the database — but Python has the libraries needed for Sentence-BERT and is the more natural language for the math-heavy similarity work. Keeping them separate means each part does the job it's best suited for.

**Why a queue:** the similarity check is an external HTTP call that can take several seconds (longer still when it's also searching and scraping the live web), so it's dispatched to a background job right after the submission is saved, instead of running inline while the student waits on the submit request. A queue worker process picks the job up and calls the Python service asynchronously; the student sees "Submission received" immediately, and the similarity report appears once the job finishes.

## 4. Data Flow — What Happens When a Student Submits Work

Step by step, from submission to a teacher seeing a flagged result:

1. **Student submits an assignment** through the Laravel web form — either pasted directly or as an uploaded `.docx` file (text is extracted from the `.docx` server-side; the file itself isn't kept). The text is saved to the `submissions` table in MySQL, and a background job is queued rather than run immediately.

2. **A queue worker picks up the job** and sends the new submission to the Python service, along with the assignment ID (so it knows which other submissions to compare against) and a flag for whether to also check the live web.

3. **The Python service loads all previous submissions** for that same assignment from the database — excluding any other submissions by the same student, so a resubmission is never compared against the student's own earlier attempt.

4. **For each existing submission, the Python service runs both algorithms:**

    - Lexical: shingling + Jaccard similarity
    - Semantic: SBERT embeddings + cosine similarity

5. **In parallel, if web checking is enabled, the service also searches the live web** for the submission's most distinctive sentences, scrapes the top results, and scores the submission against the best-matching chunk of each page using the same two algorithms.

6. **Every score is combined** into one final similarity score per comparison — peer or web — using the weighted formula above.

7. **Results are sent back to Laravel** as JSON, and saved into the `similarity_reports` table — one row per comparison, storing the lexical score, semantic score, combined score, source (another submission or a web page, with its URL), and status (pending/reviewed/dismissed/confirmed). A submission-vs-submission comparison already checked from the other student's side isn't re-created, so each real pair gets exactly one row.

8. **If the combined score crosses the assignment's threshold**, the report is automatically marked pending and shows up on the teacher's review dashboard.

9. **The teacher opens the flagged report** and sees a side-by-side view — the submission against either another student's submission or the matched web passage — with overlapping phrases highlighted (from the lexical/shingling step), plus the semantic score shown separately so the teacher understands _why_ it was flagged.

10. **The teacher marks the report** as confirmed (real plagiarism), reviewed, or dismissed (false positive, e.g., both students used a common textbook phrase). This decision is saved and shown later in the analytics dashboard.

11. **The teacher chooses when to release the result to the student.** Until then, the student's own receipt page shows only "In progress" — never the score, the matched text, or who/what it matched. Once released, the student sees a coarse label only ("Reviewed — no action needed", "Flagged — contact your instructor", etc.), never the underlying report.

## 5. Roles in the System

- **Student:** submits work, sees their own assignments and due dates, and — once a teacher releases it — a coarse status on their own submission (never the score or matched text). Enrollment is automatic: a student sees every course under their own faculty/semester, with no separate enrollment step or roster to manage.
- **Teacher:** creates assignments (courses/semesters are admin-managed), reviews flagged submissions, confirms or dismisses flags, sets the similarity threshold per assignment, and decides when to release a result to the student.
- **Admin:** manages the faculty/semester/course structure and has the same review capability as a course's teacher, plus platform-wide analytics — most-flagged students, most common assignments for flags, and a breakdown of how many flags were driven mainly by lexical matches vs. semantic matches vs. web matches.

## 6. Why This Design Is a Good Fit for a Final-Year Project

- The core algorithms (shingling, Jaccard similarity, cosine similarity) are implemented from scratch — not pulled from a plagiarism-detection library — which satisfies the requirement to build original program modules rather than relying on predefined tools. The same from-scratch scoring is reused as-is for web matches, rather than writing a second detector — one algorithm, applied to two different sources of comparison text.
- The only external, pretrained component is the Sentence-BERT language model used to generate embeddings — using a pretrained model for this specific step is a normal and widely accepted practice, since training a language model from scratch is unreasonable within a single semester. The web search and page-fetching (SerpApi, `requests`, BeautifulSoup) are similarly off-the-shelf plumbing, not part of the similarity algorithm itself.
- The result is more than a simple CRUD app: it involves real algorithm design, a meaningful data flow between two services (made asynchronous by a queue so it scales past a handful of submissions), and a genuine design decision (why combine two different similarity signals instead of relying on just one, and why check the open web as well as classmates) that can be explained and defended.