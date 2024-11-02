# Studying Portal

Create a course portal where customers can enroll in courses and unlock achievements based on their interactions with the courses.

## Installation

1. **Clone the repository:**

    ```sh
    git clone https://github.com/y-shaker/studying_portal.git
    cd studying_portal
    ```

2. **Install dependencies:**

    ```sh
    composer install
    ```

3. **Copy the `.env.example` file to `.env` and configure your environment variables:**

    ```sh
    cp .env.example .env
    ```

4. **Generate an application key:**

    ```sh
    php artisan key:generate
    ```

5. **Run the database migrations:**

    ```sh
    php artisan migrate
    ```

## Running the Application

1. **Start the development server:**

    ```sh
    php artisan serve
    ```

2. **Open your browser and visit:**

    ```
    http://localhost:8000
    ```

5. **Run End points on Postman**
    ```
    http://localhost:8000/api
    ```

## Testing

To run the tests, use the following command:

```sh
php artisan test