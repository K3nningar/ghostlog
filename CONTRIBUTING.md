<h1>Contributing Guide</h1>

<p>Thank you for your interest in this project! This document outlines the rules and best practices for contributing effectively.</p>

<h2>Project Philosophy</h2>

<p>This project is <strong>free and open source</strong>. You are free to use, modify, and redistribute it in accordance with its license. However, to preserve the coherence and long-term sustainability of the project, we apply a clear policy regarding direct contributions.</p>

<h2>Contribution Policy</h2>

<h3>Forking is mandatory</h3>

<p>It is not possible to push changes directly to the branches of the main repository. Any contribution must go through a <strong>fork</strong>:</p>

<ol>
    <li>Fork the repository to your own GitHub account.</li>
    <li>Create a dedicated branch for your change (<code>feature/my-feature</code>, <code>fix/my-fix</code>, etc.).</li>
    <li>Develop and test your changes in your fork.</li>
    <li>Open a <strong>Pull Request</strong> to the main repository once your changes are ready.</li>
</ol>

<p>This approach allows us to:</p>
<ul>
    <li>Keep a clean and readable history on the main repository.</li>
    <li>Facilitate code review.</li>
    <li>Allow everyone to freely experiment without impacting the project.</li>
</ul>

<h3>Upstreaming ideas to the main project</h3>

<p>Even though forking is encouraged to develop your own variants of the project, <strong>we strongly encourage upstreaming as many ideas, fixes, and improvements as possible back to the main repository</strong>, via a Pull Request.</p>

<p>The goal is to avoid fragmenting the project and to allow the whole community to benefit from improvements, rather than leaving fixes or new features isolated in individual forks.</p>

<p>Before developing a feature that you think could be useful to everyone:</p>
<ul>
    <li>Open an <strong>issue</strong> to discuss it with the maintainers.</li>
    <li>Check that a similar idea isn't already being discussed or developed.</li>
    <li>Once validated, develop it in your fork and then submit a Pull Request.</li>
</ul>

<p>If your idea is too specific to your personal use case and doesn't fit the main project, you are free to keep it in your fork — that's precisely the point of this project's free and forkable model.</p>

<h2>How to Contribute</h2>

<h3>1. Report a bug</h3>

<ul>
    <li>Check that the bug hasn't already been reported in the issues.</li>
    <li>Open a new issue with:
        <ul>
            <li>A clear description of the problem.</li>
            <li>Steps to reproduce the bug.</li>
            <li>Expected vs. actual behavior.</li>
            <li>Your environment (OS, version, etc.).</li>
        </ul>
    </li>
</ul>

<h3>2. Propose a feature</h3>

<ul>
    <li>Open an issue with the <code>enhancement</code> label describing your idea.</li>
    <li>Wait for feedback from the maintainers before starting development, to avoid unnecessary work if the idea is not accepted.</li>
</ul>

<h3>3. Submit a Pull Request</h3>

<ol>
    <li>Fork the project.</li>
    <li>Clone your fork: <code>git clone https://github.com/your-username/project-name.git</code></li>
    <li>Create a branch: <code>git checkout -b feature/my-feature</code></li>
    <li>Commit your changes with clear messages:<br>
        <code>git commit -m "Add: short description of the feature"</code></li>
    <li>Push to your fork: <code>git push origin feature/my-feature</code></li>
    <li>Open a Pull Request to the main branch of the upstream repository.</li>
</ol>

<h3>Best practices for PRs</h3>

<ul>
    <li>One PR = one feature or one fix (avoid catch-all PRs).</li>
    <li>Clearly describe what your PR does and why.</li>
    <li>Reference the related issue if applicable (<code>Closes #123</code>).</li>
    <li>Make sure the code follows the project's conventions (see section below).</li>
    <li>Add or update tests if necessary.</li>
    <li>Make sure documentation is up to date.</li>
</ul>

<h2>Code Conventions</h2>

<ul>
    <li>Try to keep the most same naming convention as it is in the main project.</li>
</ul>

<h2>Review Process</h2>

<ul>
    <li>Maintainers will review your PR as soon as possible.</li>
    <li>Changes may be requested before merging.</li>
    <li>Once approved, your PR will be merged into the main branch.</li>
</ul>

<h2>License</h2>

<p>By contributing to this project, you agree that your contributions will be licensed under the same license as the project (see LICENSE).</p>

<hr>

<p>Thank you for contributing to this project and being part of its community!</p>

<hr>

<p><em>Feel free to adapt the sections in brackets according to your project's technical specifics (language, testing tools, linters, etc.).</em></p>
