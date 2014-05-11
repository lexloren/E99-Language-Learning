Xenogloss
==========
Xenogloss is a browser-based web application designed to facilitate foreign-language vocabulary acquisition. The application offers both flashcard-style spaced-repetition practice with an integrated character dictionary and features designed to share and test vocabulary in a classroom setting. Spaced repetition, in which students see cards at varying intervals over time, can be twice as effective as massed repetition in promoting language acquisition. The SuperMemo algorithm, developed by Piotr Woźniak, has reportedly achieved a retention rate of 95%. Xenogloss’ practice algorithm is loosely based on the one used by SuperMemo (SM2), so we expect it will offer comparable benefits in foreign-language pedagogy.

Xenogloss provides the greatest benefits to students studying such complex, non-phonetic languages as Japanese and Chinese. Flashcard-style entities contain target words in non-alphabetic script, the translations of those words, and their foreign-language pronunciations. Students can customize which parts of the words they wish to quiz themselves on as they practice. A student raised in a bilingual household, for example, may have needs different from those of a student trying to sound out her first non-English sentence; we have created an optimal environment for both such users.

Xenogloss offers new and useful features to both students and instructors. Students can, at any time during practice, look up any individual character in an integrated dictionary, seeing the meanings of the character, other words that contain it, and user-created examples and annotations. This kind of lookup gives students hooks to make new vocabulary stick and suggests directions for further study. Instructors of courses can provide both quantitative and qualitative feedback to their students through an intelligent grading system that ensures that identical responses get graded only once for all students.

The application also includes scripts to prepopulate MySQL database with Japanese and Chinese language dictionaries to bootstrap the setup process for learning those languages.

Application code is placed in the following 
1. apis		- Backend RESTful API invocation scripts written in PHP.
2. backend	- All the backend PHP code with relevant classes invoked from API scripts.
3. frontend	- Frontend html/css and Javascript codes.
4. phptests	- Unit-test scripts for both apis & backend php scripts.
5. tools	- DB setup php and sql scripts.
6. Router.php	- Entry point to the backend that routes the http request to appropriate API script.
7. *.php	- Open APIs exposed to the frontend for invocation.
8. dictionary_upload - Scripts and raw datafiles to upload Japanese and Chinese language dictionaries.
