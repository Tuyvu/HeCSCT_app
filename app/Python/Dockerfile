FROM python:3.11-slim

WORKDIR /app

# Cập nhật và cài đặt system dependencies
RUN apt-get update && apt-get install -y \
    gcc \
    g++ \
    && rm -rf /var/lib/apt/lists/*

# Upgrade pip
RUN pip install --upgrade pip

# Cài đặt trực tiếp tất cả dependencies
RUN pip install \
    fastapi \
    uvicorn[standard] \
    pydantic \
    typing \
    networkx

# Copy source code
COPY . .

# Chạy ứng dụng
CMD ["uvicorn", "app:app", "--host", "0.0.0.0", "--port", "5000", "--reload"]

# cập nhât code "" docker cp ./app/python/app.py shovi_plants-ai:/app/app.py