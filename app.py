import sqlite3
import os
from flask import Flask, render_template, request, redirect, url_for, jsonify

app = Flask(__name__)
DB_PATH = os.path.join(os.path.dirname(__file__), "products.db")


def get_db():
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    return conn


def init_db():
    with get_db() as conn:
        conn.execute("""
            CREATE TABLE IF NOT EXISTS products (
                id       INTEGER PRIMARY KEY AUTOINCREMENT,
                barcode  TEXT    NOT NULL UNIQUE,
                name     TEXT    NOT NULL,
                price    REAL    NOT NULL
            )
        """)
        conn.commit()


# ── Pages ──────────────────────────────────────────────────────────────────────

@app.route("/")
def index():
    query = request.args.get("q", "").strip()
    with get_db() as conn:
        if query:
            products = conn.execute(
                "SELECT * FROM products WHERE name LIKE ? OR barcode LIKE ? ORDER BY name",
                (f"%{query}%", f"%{query}%"),
            ).fetchall()
        else:
            products = conn.execute("SELECT * FROM products ORDER BY name").fetchall()
    return render_template("index.html", products=products, query=query)


@app.route("/scanner")
def scanner():
    return render_template("scanner.html")


# ── CRUD ───────────────────────────────────────────────────────────────────────

@app.route("/product/add", methods=["POST"])
def add_product():
    barcode = request.form["barcode"].strip()
    name    = request.form["name"].strip()
    price   = float(request.form["price"])
    with get_db() as conn:
        conn.execute(
            "INSERT OR REPLACE INTO products (barcode, name, price) VALUES (?, ?, ?)",
            (barcode, name, price),
        )
        conn.commit()
    return redirect(url_for("index"))


@app.route("/product/edit/<int:pid>", methods=["POST"])
def edit_product(pid):
    barcode = request.form["barcode"].strip()
    name    = request.form["name"].strip()
    price   = float(request.form["price"])
    with get_db() as conn:
        conn.execute(
            "UPDATE products SET barcode=?, name=?, price=? WHERE id=?",
            (barcode, name, price, pid),
        )
        conn.commit()
    return redirect(url_for("index"))


@app.route("/product/delete/<int:pid>", methods=["POST"])
def delete_product(pid):
    with get_db() as conn:
        conn.execute("DELETE FROM products WHERE id=?", (pid,))
        conn.commit()
    return redirect(url_for("index"))


# ── API ────────────────────────────────────────────────────────────────────────

@app.route("/api/lookup")
def api_lookup():
    barcode = request.args.get("barcode", "").strip()
    if not barcode:
        return jsonify({"found": False})
    with get_db() as conn:
        row = conn.execute(
            "SELECT * FROM products WHERE barcode=?", (barcode,)
        ).fetchone()
    if row:
        return jsonify({"found": True, "name": row["name"], "price": row["price"], "barcode": row["barcode"]})
    return jsonify({"found": False, "barcode": barcode})


if __name__ == "__main__":
    init_db()
    app.run(debug=True, host="0.0.0.0", port=5000)
