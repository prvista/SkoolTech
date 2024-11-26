# report_card.py
import json
import mysql.connector

def generate_report_cards():
    # Database connection
    conn = mysql.connector.connect(
        host='localhost',
        user='root',
        password='',
        database='skooltech'
    )
    cursor = conn.cursor(dictionary=True)

    # Fetch quiz results
    cursor.execute("""
        SELECT s.id AS student_id, s.student_number, s.username, s.name, q.subject, qr.score 
        FROM quiz_results qr
        JOIN students s ON qr.student_id = s.id
        JOIN quizzes q ON qr.quiz_id = q.id
    """)
    results = cursor.fetchall()

    report_cards = {}
    for row in results:
        student_id = row['student_id']
        if student_id not in report_cards:
            report_cards[student_id] = {
                'student_number': row['student_number'],
                'username': row['username'],
                'name': row['name'],
                'subjects': {}
            }
        if row['subject'] not in report_cards[student_id]['subjects']:
            report_cards[student_id]['subjects'][row['subject']] = []
        report_cards[student_id]['subjects'][row['subject']].append(row['score'])

    conn.close()
    return json.dumps(report_cards)

if __name__ == '__main__':
    print(generate_report_cards())
