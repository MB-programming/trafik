import sqlite3
import os
import anthropic

from flask import Flask, render_template, request, redirect, url_for, jsonify
from dotenv import load_dotenv

load_dotenv()

app = Flask(__name__)
DB_PATH = os.path.join(os.path.dirname(__file__), "products.db")

CATEGORIES = ["غذائية", "سجاير", "مشروبات", "منوعات"]


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
                price    REAL    NOT NULL,
                category TEXT    NOT NULL DEFAULT 'منوعات'
            )
        """)
        # migrate older schema that lacks category
        try:
            conn.execute("ALTER TABLE products ADD COLUMN category TEXT NOT NULL DEFAULT 'منوعات'")
        except Exception:
            pass
        conn.commit()


# ── Pages ──────────────────────────────────────────────────────────────────────

@app.route("/")
def index():
    query    = request.args.get("q", "").strip()
    category = request.args.get("cat", "").strip()
    with get_db() as conn:
        sql    = "SELECT * FROM products WHERE 1=1"
        params = []
        if query:
            sql += " AND (name LIKE ? OR barcode LIKE ?)"
            params += [f"%{query}%", f"%{query}%"]
        if category and category in CATEGORIES:
            sql += " AND category = ?"
            params.append(category)
        sql += " ORDER BY name"
        products = conn.execute(sql, params).fetchall()
        counts   = {c: conn.execute(
            "SELECT COUNT(*) FROM products WHERE category=?", (c,)
        ).fetchone()[0] for c in CATEGORIES}
        total    = conn.execute("SELECT COUNT(*) FROM products").fetchone()[0]
    return render_template("index.html",
                           products=products,
                           query=query,
                           active_cat=category,
                           categories=CATEGORIES,
                           counts=counts,
                           total=total)


@app.route("/scanner")
def scanner():
    return render_template("scanner.html")


@app.route("/fastscan")
def fastscan():
    has_key = bool(os.environ.get("ANTHROPIC_API_KEY"))
    return render_template("fastscan.html", has_key=has_key)


# ── CRUD ───────────────────────────────────────────────────────────────────────

@app.route("/product/add", methods=["POST"])
def add_product():
    barcode  = request.form["barcode"].strip()
    name     = request.form["name"].strip()
    price    = float(request.form["price"])
    category = request.form.get("category", "منوعات")
    if category not in CATEGORIES:
        category = "منوعات"
    with get_db() as conn:
        conn.execute(
            "INSERT OR REPLACE INTO products (barcode, name, price, category) VALUES (?,?,?,?)",
            (barcode, name, price, category),
        )
        conn.commit()
    return redirect(url_for("index"))


@app.route("/product/edit/<int:pid>", methods=["POST"])
def edit_product(pid):
    barcode  = request.form["barcode"].strip()
    name     = request.form["name"].strip()
    price    = float(request.form["price"])
    category = request.form.get("category", "منوعات")
    if category not in CATEGORIES:
        category = "منوعات"
    with get_db() as conn:
        conn.execute(
            "UPDATE products SET barcode=?, name=?, price=?, category=? WHERE id=?",
            (barcode, name, price, category, pid),
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
        return jsonify({"found": True, "name": row["name"],
                        "price": row["price"], "category": row["category"],
                        "barcode": row["barcode"]})
    return jsonify({"found": False, "barcode": barcode})


@app.route("/api/identify", methods=["POST"])
def api_identify():
    """Identify a product from a base64 image using Claude Vision, then look it up in the DB."""
    api_key = os.environ.get("ANTHROPIC_API_KEY")
    if not api_key:
        return jsonify({"found": False, "error": "no_api_key"})

    data      = request.get_json(force=True)
    image_b64 = data.get("image", "")
    if not image_b64:
        return jsonify({"found": False, "error": "no_image"})

    # strip data-URL prefix
    if "," in image_b64:
        image_b64 = image_b64.split(",", 1)[1]

    try:
        client = anthropic.Anthropic(api_key=api_key)
        msg = client.messages.create(
            model="claude-haiku-4-5-20251001",
            max_tokens=120,
            messages=[{
                "role": "user",
                "content": [
                    {
                        "type": "image",
                        "source": {
                            "type": "base64",
                            "media_type": "image/jpeg",
                            "data": image_b64,
                        },
                    },
                    {
                        "type": "text",
                        "text": (
                            "ما هو المنتج أو العلامة التجارية الظاهرة في هذه الصورة؟ "
                            "أجب فقط باسم المنتج أو البراند بدون أي شرح إضافي. "
                            "إذا كان هناك باركود أو نص على العبوة استخدمه. "
                            "لا تتجاوز 10 كلمات."
                        ),
                    },
                ],
            }],
        )
        identified = msg.content[0].text.strip()
    except Exception as e:
        return jsonify({"found": False, "error": str(e)})

    # search DB — full phrase first, then word-by-word
    with get_db() as conn:
        row = conn.execute(
            "SELECT * FROM products WHERE LOWER(name) LIKE LOWER(?) LIMIT 1",
            (f"%{identified}%",),
        ).fetchone()

        if not row:
            for word in identified.split():
                if len(word) > 2:
                    row = conn.execute(
                        "SELECT * FROM products WHERE LOWER(name) LIKE LOWER(?) LIMIT 1",
                        (f"%{word}%",),
                    ).fetchone()
                    if row:
                        break

    if row:
        return jsonify({
            "found":         True,
            "name":          row["name"],
            "price":         row["price"],
            "category":      row["category"],
            "identified_as": identified,
        })
    return jsonify({"found": False, "identified_as": identified})


if __name__ == "__main__":
    init_db()
    app.run(debug=True, host="0.0.0.0", port=5000)
