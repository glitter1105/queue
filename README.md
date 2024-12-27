# 排队叫号系统/Queue Management System / Number Calling System
This project is built using the Webman framework, which is a high-performance PHP framework for building web applications.
使用webman以及gatwayworker实现的简单排队叫号系统

## Features

- PHP
- JavaScript
- Composer
- Bootstrap

## Requirements

- PHP 7.4 or higher
- Composer

## Installation

1. **Clone the repository:**

   ```sh
   git clone https://github.com/zx2020-07/queue.git
   cd queue
   ```

2. **Install PHP dependencies:**

   ```sh
   composer install
   ```

3. **Install JavaScript dependencies:**

   ```sh
    Import the SQL file located at sql/queue.sql.
   ```

4. **Set up environment variables:**

   Copy the `.env.example` file to `.env` and adjust the settings as needed.

   ```sh
   cp .env.example .env
   ```

5. **Run the application:**

   ```sh
   php start.php start
   ```

   The application will be available at `http://localhost:8787`.

## Usage

- Access the application in your web browser at `http://localhost:8787`.
- To view the queue status, navigate to `/status.html`.

## Example
![管理页面](https://github.com/user-attachments/assets/c7e49976-ec04-4dfa-85af-f8ca4a598c91)
![窗口管理](https://github.com/user-attachments/assets/0aa1ea4d-cb62-4715-bdd5-9f4d250da236)
![排队取号](https://github.com/user-attachments/assets/f2a33d9a-e5fd-4a90-8e63-3e8ceb5f8264)
![排队取号2](https://github.com/user-attachments/assets/4cfa1008-965f-4454-a8fa-b1d6548a13b3)
![排队状态](https://github.com/user-attachments/assets/64c3b894-ac67-41a9-8368-b0bc5383240e)
![显示大屏](https://github.com/user-attachments/assets/77584d69-cd1d-428b-8bbb-f982f2503ddf)








## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
