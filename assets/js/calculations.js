/**
 * Web-Based Academic Transcript Generating System with QR Verification
 * Kano State Polytechnic - Department of Computer Science
 * GPA & CGPA Calculation Engine
 */

const AcademicCalculations = {
  /**
   * Calculate Quality Point for an individual course enrollment
   * Quality Point = Credit Unit * Grade Point
   */
  calculateQualityPoint(creditUnit, gradePoint) {
    const unit = parseFloat(creditUnit) || 0;
    const gp = parseFloat(gradePoint) || 0;
    return parseFloat((unit * gp).toFixed(2));
  },

  /**
   * Calculate GPA for a single semester
   * Formula: GPA = Total Quality Points / Total Credit Units
   */
  calculateSemesterGPA(results = []) {
    let totalCreditUnits = 0;
    let totalQualityPoints = 0;

    results.forEach(res => {
      const unit = parseFloat(res.creditUnit) || 0;
      const qp = parseFloat(res.qualityPoint) || (unit * (parseFloat(res.gradePoint) || 0));
      totalCreditUnits += unit;
      totalQualityPoints += qp;
    });

    const gpa = totalCreditUnits > 0 ? parseFloat((totalQualityPoints / totalCreditUnits).toFixed(2)) : 0.00;

    return {
      totalCreditUnits,
      totalQualityPoints: parseFloat(totalQualityPoints.toFixed(2)),
      gpa: gpa.toFixed(2),
      courseCount: results.length
    };
  },

  /**
   * Calculate Cumulative CGPA across all completed semesters
   * Formula: CGPA = Cumulative Quality Points / Cumulative Credit Units
   */
  calculateCumulativeCGPA(allResults = []) {
    let cumulativeCreditUnits = 0;
    let cumulativeQualityPoints = 0;

    allResults.forEach(res => {
      const unit = parseFloat(res.creditUnit) || 0;
      const qp = parseFloat(res.qualityPoint) || (unit * (parseFloat(res.gradePoint) || 0));
      cumulativeCreditUnits += unit;
      cumulativeQualityPoints += qp;
    });

    const cgpa = cumulativeCreditUnits > 0 ? parseFloat((cumulativeQualityPoints / cumulativeCreditUnits).toFixed(2)) : 0.00;

    return {
      cumulativeCreditUnits,
      cumulativeQualityPoints: parseFloat(cumulativeQualityPoints.toFixed(2)),
      cgpa: cgpa.toFixed(2),
      totalCourses: allResults.length,
      classification: api.grading.getClassification(cgpa)
    };
  },

  /**
   * Group student results into hierarchical Sessions & Semesters with progressive GPA and CGPA
   */
  getStudentAcademicProgression(allStudentResults = []) {
    // Sort logically by session and semester
    const grouped = {};

    allStudentResults.forEach(res => {
      const sessionKey = res.sessionName || "2023/2024";
      const semKey = res.semester || "First Semester";
      const groupKey = `${sessionKey}__${semKey}`;

      if (!grouped[groupKey]) {
        grouped[groupKey] = {
          sessionName: sessionKey,
          semester: semKey,
          level: res.level || "ND I",
          results: [],
          semesterSummary: null,
          runningCGPA: null
        };
      }
      grouped[groupKey].results.push(res);
    });

    const semestersArray = Object.values(grouped);

    // Compute running Cumulative CGPA after each semester
    let runningUnits = 0;
    let runningQualityPoints = 0;

    semestersArray.forEach(semGroup => {
      const semSummary = this.calculateSemesterGPA(semGroup.results);
      semGroup.semesterSummary = semSummary;

      runningUnits += semSummary.totalCreditUnits;
      runningQualityPoints += semSummary.totalQualityPoints;

      const runningCGPA = runningUnits > 0 ? (runningQualityPoints / runningUnits).toFixed(2) : "0.00";
      semGroup.runningCumulative = {
        cumulativeCreditUnits: runningUnits,
        cumulativeQualityPoints: parseFloat(runningQualityPoints.toFixed(2)),
        cgpa: runningCGPA,
        classification: api.grading.getClassification(runningCGPA)
      };
    });

    const overallCumulative = this.calculateCumulativeCGPA(allStudentResults);

    return {
      semesters: semestersArray,
      overall: overallCumulative
    };
  }
};

window.AcademicCalculations = AcademicCalculations;
