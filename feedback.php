<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Feedback</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
<style>
  
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

 
 :root {
 
  --navy: #0b1f3a;
  --navy-md: #132d52;
  --navy-lt: #1e4175;

  --gold: #c8963e;
  --gold-lt: #e0b96a;

  --cream: #f7f4ef;
  --cream-dk: #ede8e0;

  --white: #ffffff;
  --text: #1a2535;
  --muted: #5a6a80;

  --error: #c0392b;
  --error-bg: #fdf0f0;
  --error-border: #f5c6cb;

  --radius: 10px;
  --transition: 0.2s ease;

  --accent: var(--gold);
  --accent-hover: var(--gold-lt);
  --bg: linear-gradient(135deg, #0b1f3a, #132d52, #1e4175);
  --surface: rgba(255,255,255,0.95);
  --border: var(--cream-dk);
  --input-bg: var(--cream);
}

  html { scroll-behavior: smooth; }

  body {
  font-family: 'Poppins', sans-serif;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;

  background: var(--bg);
  padding: 20px;
  position: relative;
  overflow: hidden;
}

  .card {
  background: var(--surface);
  border-radius: 18px;
  box-shadow: 0 24px 60px rgba(0,0,0,0.25);
  padding: 40px 36px;
  width: 100%;
  max-width: 560px;
  border: 1px solid rgba(255,255,255,0.3);
}

  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  .card-header { margin-bottom: 28px; }

  .pill {
    display: inline-block;
    font-size: .7rem;
    font-weight: 600;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--accent);
    background: rgba(116,148,236,0.1);
    border: 1px solid rgba(116,148,236,0.3);
    border-radius: 100px;
    padding: .25rem .85rem;
    margin-bottom: .9rem;
  }

  h1 {
    font-size: clamp(1.4rem, 4vw, 1.75rem);
    font-weight: 700;
    line-height: 1.25;
    color: var(--text);
  }
  h1 em { color: var(--accent); font-style: normal; }

  .subtitle {
    margin-top: .45rem;
    font-size: .85rem;
    color: var(--muted);
    line-height: 1.6;
  }

  .grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
  }

  @media (max-width: 480px) {
    .card { padding: 28px 20px 32px; }
    .grid-2 { grid-template-columns: 1fr; }
  }

  .field { display: flex; flex-direction: column; gap: .4rem; margin-bottom: 18px; }
  .field:last-child { margin-bottom: 0; }

  label {
    display: block;
    font-size: .8rem;
    font-weight: 600;
    color: var(--text);
  }
  label span.req { color: var(--accent); margin-left: 2px; }

  input, select, textarea {
    width: 100%;
    padding: 12px 14px;
    background: var(--input-bg);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    font-family: 'Poppins', sans-serif;
    font-size: .9rem;
    color: var(--text);
    outline: none;
    transition: border-color var(--transition), box-shadow var(--transition), background var(--transition);
    -webkit-appearance: none;
  }

  input::placeholder, textarea::placeholder { color: var(--muted); }

  select {
    cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%238a90a8' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    background-color: var(--input-bg);
    padding-right: 2.5rem;
  }

  input:focus, select:focus, textarea:focus {
  border-color: var(--gold);
  box-shadow: 0 0 0 3px rgba(200,150,62,0.2);
  background: #fff;
}

 input.error, select.error, textarea.error {
  border-color: var(--error);
  box-shadow: 0 0 0 3px rgba(192,57,43,0.08);
}
  textarea { resize: vertical; min-height: 110px; line-height: 1.6; }

  .stars-wrap { display: flex; flex-direction: row-reverse; gap: .3rem; justify-content: flex-end; }

  .stars-wrap input[type="radio"] { display: none; }

  .stars-wrap label {
    font-size: 1.6rem;
    color: #dde1f0;
    cursor: pointer;
    transition: color var(--transition), transform var(--transition);
    text-transform: none;
    letter-spacing: 0;
    font-weight: 400;
    padding: 0;
  }

  .stars-wrap label::before { content: '★'; }

  .stars-wrap input:checked ~ label,
  .stars-wrap label:hover,
  .stars-wrap label:hover ~ label {
    color: var(--accent);
  }

  .stars-wrap label:hover { transform: scale(1.15); }

  .field-footer {
    display: flex;
    justify-content: flex-end;
    font-size: .72rem;
    color: var(--muted);
    margin-top: .2rem;
  }

  .field-footer.warn { color: var(--error); }

  .btn-submit {
  background: var(--gold);
  color: white;

  border: none;
  border-radius: 12px;

  padding: 13px 16px;

  font-weight: 600;
  font-size: 0.95rem;

  cursor: pointer;


  box-shadow: 0 10px 25px rgba(200,150,62,0.25);

  transition: all 0.25s ease;

  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-submit:hover:not(:disabled) {
  background: var(--gold-lt);
  transform: translateY(-2px);

  box-shadow: 0 14px 35px rgba(200,150,62,0.35);
}
  .btn-submit:active:not(:disabled) { transform: translateY(0); }
  .btn-submit:disabled { opacity: .6; cursor: not-allowed; }

  .btn-submit .spinner {
    display: none;
    width: 18px; height: 18px;
    border: 2.5px solid rgba(255,255,255,.35);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin .7s linear infinite;
    margin: 0 auto;
  }
  @keyframes spin { to { transform: rotate(360deg); } }

  .btn-submit.loading .btn-text { display: none; }
  .btn-submit.loading .spinner  { display: block; }

  #toast {
    position: fixed;
    top: 1.5rem;
    left: 50%;
    transform: translateX(-50%) translateY(-120%);
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: .85rem 1.4rem;
    font-size: .88rem;
    font-family: 'Poppins', sans-serif;
    display: flex;
    align-items: center;
    gap: .6rem;
    box-shadow: 0 8px 32px rgba(116,148,236,0.2);
    z-index: 1000;
    transition: transform .4s cubic-bezier(.175,.885,.32,1.275);
    max-width: 90vw;
    white-space: nowrap;
    color: var(--text);
  }
  #toast.show { transform: translateX(-50%) translateY(0); }
  #toast.success { border-color: rgba(116,148,236,.5); color: var(--accent); }
  #toast.failure { border-color: var(--error-border); color: var(--error); }

  #success-msg {
    display: none;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 1.5rem 0;
    animation: fadeUp .4s ease both;
  }

  .check-circle {
    width: 64px; height: 64px;
    border-radius: 50%;
    background: rgba(116,148,236,0.1);
    border: 2px solid var(--accent);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.8rem;
    margin-bottom: 1.2rem;
    color: var(--accent);
  }

  #success-msg h2 { font-size: 1.4rem; font-weight: 700; color: var(--text); }
  #success-msg p  { color: var(--muted); font-size: .88rem; margin-top: .5rem; }

  .success-actions {
    display: flex;
    gap: 12px;
    margin-top: 1.6rem;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;
  }

  .btn-again,
  .btn-home {
    padding: .65rem 1.6rem;
    border-radius: var(--radius);
    font-family: 'Poppins', sans-serif;
    font-size: .85rem;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    height: 40px;
    box-sizing: border-box;
    white-space: nowrap;
  }

  .btn-again {
    background: transparent;
    border: 1.5px solid var(--border);
    color: var(--muted);
    transition: border-color var(--transition), color var(--transition);
  }
  .btn-again:hover { border-color: var(--accent); color: var(--accent); }

  .btn-home {
    background: var(--accent);
    border: 1.5px solid var(--accent);
    color: #fff;
    font-weight: 600;
    transition: background var(--transition), transform .15s;
  }
  .btn-home:hover { background: var(--accent-hover); transform: translateY(-1px); }

  .hint { font-size: .75rem; color: var(--error); display: none; margin-top: .15rem; }
  .hint.show { display: block; }
</style>
</head>
<body>

<div id="toast" role="alert" aria-live="polite"></div>

<div class="card" role="main">

  <div class="card-header">
    <span class="pill">Feedback</span>
    <h1>Tell us what you <em>think</em></h1>
    <p class="subtitle">Your feedback helps us improve. We read every single response.</p>
  </div>

  <form id="feedbackForm" novalidate>

    <div class="grid-2">
      <div class="field">
        <label for="name">Full Name <span class="req">*</span></label>
        <input type="text" id="name" name="name" placeholder="name" maxlength="100" autocomplete="name" />
        <span class="hint" id="name-hint">Please enter your name.</span>
      </div>

      <div class="field">
        <label for="email">Email <span class="req">*</span></label>
        <input type="email" id="email" name="email" placeholder="email" maxlength="150" autocomplete="email" />
        <span class="hint" id="email-hint">Enter a valid email address.</span>
      </div>
    </div>

    <div class="grid-2">
      <div class="field">
        <label for="category">Category <span class="req">*</span></label>
        <select id="category" name="category">
          <option value="" disabled selected>Select one…</option>
          <option value="product">Product</option>
          <option value="support">Support</option>
          <option value="other">Other</option>
        </select>
        <span class="hint" id="category-hint">Please select a category.</span>
      </div>

      <div class="field">
        <label>Rating <span class="req">*</span></label>
        <div class="stars-wrap" id="stars-wrap" role="radiogroup" aria-label="Rating">
          <input type="radio" name="rating" id="s5" value="5" /><label for="s5" title="5 stars"></label>
          <input type="radio" name="rating" id="s4" value="4" /><label for="s4" title="4 stars"></label>
          <input type="radio" name="rating" id="s3" value="3" /><label for="s3" title="3 stars"></label>
          <input type="radio" name="rating" id="s2" value="2" /><label for="s2" title="2 stars"></label>
          <input type="radio" name="rating" id="s1" value="1" /><label for="s1" title="1 star"></label>
        </div>
        <span class="hint" id="rating-hint">Please select a rating.</span>
      </div>
    </div>

    <div class="field">
      <label for="message">Message <span class="req">*</span></label>
      <textarea id="message" name="message" placeholder="Share your experience, suggestions, or anything on your mind…" maxlength="2000"></textarea>
      <div class="field-footer" id="char-counter">0 / 2000</div>
      <span class="hint" id="message-hint">Message must be at least 10 characters.</span>
    </div>

    <button type="submit" class="btn-submit" id="submitBtn">
      <span class="btn-text">Send Feedback</span>
      <span class="spinner"></span>
    </button>

  </form>

  <div id="success-msg" aria-live="polite">
    <div class="check-circle">✓</div>
    <h2>Feedback received!</h2>
    <p>Thank you, we truly appreciate you taking the time.</p>
    <div class="success-actions">
      <a href="index.php" class="btn-home">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        Back to Home
      </a>
      <button class="btn-again" id="resetBtn">Submit another</button>
    </div>
  </div>

</div>

<script>
(function () {
  const form       = document.getElementById('feedbackForm');
  const submitBtn  = document.getElementById('submitBtn');
  const successMsg = document.getElementById('success-msg');
  const resetBtn   = document.getElementById('resetBtn');
  const msgArea    = document.getElementById('message');
  const counter    = document.getElementById('char-counter');
  const toast      = document.getElementById('toast');
  let toastTimer;

  msgArea.addEventListener('input', () => {
    const len = msgArea.value.length;
    counter.textContent = `${len} / 2000`;
    counter.classList.toggle('warn', len > 1900);
  });

  function showToast(msg, type = 'success') {
    clearTimeout(toastTimer);
    toast.textContent = (type === 'success' ? '✓  ' : '✕  ') + msg;
    toast.className = `show ${type}`;
    toastTimer = setTimeout(() => { toast.className = ''; }, 4000);
  }

  function setError(id, hintId, show) {
    const el   = document.getElementById(id);
    const hint = document.getElementById(hintId);
    el.classList.toggle('error', show);
    hint.classList.toggle('show', show);
    return show;
  }

  function validate() {
    const name     = document.getElementById('name').value.trim();
    const email    = document.getElementById('email').value.trim();
    const category = document.getElementById('category').value;
    const rating   = document.querySelector('input[name="rating"]:checked');
    const message  = msgArea.value.trim();

    let invalid = false;

    invalid |= setError('name',     'name-hint',     name.length < 2);
    invalid |= setError('email',    'email-hint',    !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email));
    invalid |= setError('category', 'category-hint', !category);
    
    const ratingHint = document.getElementById('rating-hint');
    const ratingErr  = !rating;
    ratingHint.classList.toggle('show', ratingErr);
    invalid |= ratingErr;
    invalid |= setError('message',  'message-hint',  message.length < 10);

    return !invalid;
  }

  ['name','email','category','message'].forEach(id => {
    document.getElementById(id).addEventListener('input', () => {
      document.getElementById(id).classList.remove('error');
      document.getElementById(id + '-hint').classList.remove('show');
    });
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    if (!validate()) return;

    submitBtn.disabled = true;
    submitBtn.classList.add('loading');

    const data = new FormData(form);

    try {
      const res  = await fetch('submit_feedback.php', { method: 'POST', body: data });
      const json = await res.json();

      if (json.success) {
        form.style.display        = 'none';
        successMsg.style.display  = 'flex';
      } else {
        const msgs = json.errors ? json.errors.join(' ') : (json.error || 'Something went wrong.');
        showToast(msgs, 'failure');
      }
    } catch (err) {
      showToast('Network error. Please try again.', 'failure');
    } finally {
      submitBtn.disabled = false;
      submitBtn.classList.remove('loading');
    }
  });

  resetBtn.addEventListener('click', () => {
    form.reset();
    counter.textContent      = '0 / 2000';
    successMsg.style.display = 'none';
    form.style.display       = '';
    document.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
    document.querySelectorAll('.hint.show').forEach(el => el.classList.remove('show'));
  });
})();
</script>
</body>
</html>