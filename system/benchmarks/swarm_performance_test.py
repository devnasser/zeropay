#!/usr/bin/env python3
"""
⚔️ اختبار أداء السرب الشامل ⚔️
يختبر الأداء مع أعداد مختلفة من الوحدات
"""

import time
import multiprocessing
import threading
import asyncio
import psutil
import numpy as np
from concurrent.futures import ThreadPoolExecutor, ProcessPoolExecutor
import json
from datetime import datetime

class SwarmPerformanceTester:
    def __init__(self):
        self.cpu_count = psutil.cpu_count()
        self.memory_gb = psutil.virtual_memory().total / (1024**3)
        self.results = {}
        
    def cpu_intensive_task(self, n):
        """مهمة كثيفة لـ CPU"""
        result = 0
        for i in range(n):
            result += sum([j**2 for j in range(1000)])
        return result
    
    def memory_intensive_task(self, size_mb):
        """مهمة كثيفة للذاكرة"""
        data = np.random.rand(size_mb * 1024 * 128)  # 128 = 1MB/8KB
        return data.mean()
    
    def io_intensive_task(self, iterations):
        """مهمة كثيفة لـ I/O"""
        temp_file = f"/mnt/ramdisk/tmp/test_{threading.current_thread().ident}.tmp"
        for i in range(iterations):
            with open(temp_file, 'w') as f:
                f.write("x" * 10000)
            with open(temp_file, 'r') as f:
                _ = f.read()
        return iterations
    
    async def async_network_simulation(self, requests):
        """محاكاة مهام شبكة غير متزامنة"""
        async def make_request():
            await asyncio.sleep(0.001)  # محاكاة زمن الشبكة
            return "response"
        
        tasks = [make_request() for _ in range(requests)]
        results = await asyncio.gather(*tasks)
        return len(results)
    
    def test_threading_performance(self, num_threads, workload_per_thread):
        """اختبار أداء Threading"""
        start_time = time.time()
        start_cpu = psutil.cpu_percent(interval=0.1)
        start_mem = psutil.virtual_memory().percent
        
        with ThreadPoolExecutor(max_workers=num_threads) as executor:
            futures = [executor.submit(self.cpu_intensive_task, workload_per_thread) 
                      for _ in range(num_threads)]
            results = [f.result() for f in futures]
        
        end_time = time.time()
        end_cpu = psutil.cpu_percent(interval=0.1)
        end_mem = psutil.virtual_memory().percent
        
        return {
            "duration": end_time - start_time,
            "avg_cpu": (start_cpu + end_cpu) / 2,
            "avg_memory": (start_mem + end_mem) / 2,
            "throughput": num_threads / (end_time - start_time)
        }
    
    def test_multiprocessing_performance(self, num_processes, workload_per_process):
        """اختبار أداء Multiprocessing"""
        start_time = time.time()
        start_cpu = psutil.cpu_percent(interval=0.1)
        start_mem = psutil.virtual_memory().percent
        
        with ProcessPoolExecutor(max_workers=num_processes) as executor:
            futures = [executor.submit(self.cpu_intensive_task, workload_per_process) 
                      for _ in range(num_processes)]
            results = [f.result() for f in futures]
        
        end_time = time.time()
        end_cpu = psutil.cpu_percent(interval=0.1)
        end_mem = psutil.virtual_memory().percent
        
        return {
            "duration": end_time - start_time,
            "avg_cpu": (start_cpu + end_cpu) / 2,
            "avg_memory": (start_mem + end_mem) / 2,
            "throughput": num_processes / (end_time - start_time)
        }
    
    def test_async_performance(self, num_coroutines, requests_per_coroutine):
        """اختبار أداء Async I/O"""
        async def run_test():
            start_time = time.time()
            tasks = [self.async_network_simulation(requests_per_coroutine) 
                    for _ in range(num_coroutines)]
            results = await asyncio.gather(*tasks)
            end_time = time.time()
            return {
                "duration": end_time - start_time,
                "total_requests": sum(results),
                "requests_per_second": sum(results) / (end_time - start_time)
            }
        
        return asyncio.run(run_test())
    
    def test_mixed_workload(self, num_units):
        """اختبار عبء عمل مختلط (CPU + Memory + I/O)"""
        start_time = time.time()
        start_cpu = psutil.cpu_percent(interval=0.1)
        start_mem = psutil.virtual_memory().percent
        
        # توزيع العمل: 40% CPU, 30% Memory, 30% I/O
        cpu_units = int(num_units * 0.4)
        mem_units = int(num_units * 0.3)
        io_units = int(num_units * 0.3)
        
        with ThreadPoolExecutor(max_workers=num_units) as executor:
            # CPU tasks
            cpu_futures = [executor.submit(self.cpu_intensive_task, 100) 
                          for _ in range(cpu_units)]
            # Memory tasks
            mem_futures = [executor.submit(self.memory_intensive_task, 10) 
                          for _ in range(mem_units)]
            # I/O tasks
            io_futures = [executor.submit(self.io_intensive_task, 50) 
                         for _ in range(io_units)]
            
            # جمع النتائج
            all_futures = cpu_futures + mem_futures + io_futures
            results = [f.result() for f in all_futures]
        
        end_time = time.time()
        end_cpu = psutil.cpu_percent(interval=0.1)
        end_mem = psutil.virtual_memory().percent
        
        return {
            "duration": end_time - start_time,
            "avg_cpu": (start_cpu + end_cpu) / 2,
            "avg_memory": (start_mem + end_mem) / 2,
            "tasks_completed": len(results),
            "tasks_per_second": len(results) / (end_time - start_time)
        }
    
    def find_optimal_swarm_size(self):
        """العثور على حجم السرب الأمثل"""
        test_sizes = [10, 25, 50, 75, 100, 125, 150, 200, 250, 300]
        optimal_results = {}
        
        print("🔍 البحث عن حجم السرب الأمثل...")
        print(f"📊 النظام: {self.cpu_count} CPUs, {self.memory_gb:.1f}GB RAM\n")
        
        for size in test_sizes:
            print(f"\n⚡ اختبار {size} وحدة...")
            
            # اختبار العبء المختلط
            mixed_result = self.test_mixed_workload(size)
            
            # اختبار Threading
            thread_result = self.test_threading_performance(size, 50)
            
            # اختبار Async (للأعداد المعقولة فقط)
            if size <= 200:
                async_result = self.test_async_performance(size, 100)
            else:
                async_result = {"requests_per_second": 0}
            
            efficiency = mixed_result["tasks_per_second"] / size
            
            optimal_results[size] = {
                "mixed_performance": mixed_result,
                "thread_performance": thread_result,
                "async_performance": async_result,
                "efficiency_score": efficiency,
                "cpu_saturation": mixed_result["avg_cpu"],
                "memory_usage": mixed_result["avg_memory"]
            }
            
            print(f"  ✅ الكفاءة: {efficiency:.2f}")
            print(f"  📈 CPU: {mixed_result['avg_cpu']:.1f}%")
            print(f"  💾 RAM: {mixed_result['avg_memory']:.1f}%")
        
        return optimal_results
    
    def generate_report(self, results):
        """توليد تقرير شامل"""
        print("\n" + "="*60)
        print("⚔️ تقرير أداء السرب الشامل ⚔️")
        print("="*60)
        print(f"\n📅 التاريخ: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
        print(f"🖥️ النظام: {self.cpu_count} CPUs, {self.memory_gb:.1f}GB RAM")
        
        # العثور على الحجم الأمثل
        best_size = max(results.keys(), 
                       key=lambda x: results[x]["efficiency_score"] 
                       if results[x]["cpu_saturation"] < 90 else 0)
        
        print(f"\n🏆 حجم السرب الأمثل: {best_size} وحدة")
        print(f"   الكفاءة: {results[best_size]['efficiency_score']:.3f}")
        print(f"   استخدام CPU: {results[best_size]['cpu_saturation']:.1f}%")
        print(f"   استخدام RAM: {results[best_size]['memory_usage']:.1f}%")
        
        print("\n📊 نتائج تفصيلية:")
        print("-" * 60)
        print(f"{'الحجم':>6} | {'الكفاءة':>8} | {'CPU%':>6} | {'RAM%':>6} | {'مهام/ثانية':>12}")
        print("-" * 60)
        
        for size in sorted(results.keys()):
            r = results[size]
            print(f"{size:6d} | {r['efficiency_score']:8.3f} | "
                  f"{r['cpu_saturation']:6.1f} | {r['memory_usage']:6.1f} | "
                  f"{r['mixed_performance']['tasks_per_second']:12.1f}")
        
        # حفظ النتائج
        with open('/workspace/system/benchmarks/swarm_results_latest.json', 'w') as f:
            json.dump(results, f, indent=2)
        
        return best_size

if __name__ == "__main__":
    print("⚔️ بدء اختبار أداء السرب الشامل ⚔️\n")
    
    tester = SwarmPerformanceTester()
    results = tester.find_optimal_swarm_size()
    optimal_size = tester.generate_report(results)
    
    print(f"\n✅ اكتمل الاختبار!")
    print(f"📌 الحجم الموصى به للسرب: {optimal_size} وحدة")